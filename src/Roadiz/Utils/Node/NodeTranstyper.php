<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Node;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\UrlAlias;

final class NodeTranstyper
{
    private ManagerRegistry $managerRegistry;
    private LoggerInterface $logger;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->managerRegistry = $managerRegistry;
    }

    private function getManager(): ObjectManager
    {
        $manager = $this->managerRegistry->getManagerForClass(NodesSources::class);
        if (null === $manager) {
            throw new \RuntimeException('No manager was found during transtyping.');
        }
        return $manager;
    }

    /**
     * @param NodeTypeField $oldField
     * @param NodeType      $destinationNodeType
     *
     * @return NodeTypeField|null
     */
    private function getMatchingNodeTypeField(NodeTypeField $oldField, NodeType $destinationNodeType): ?NodeTypeField
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('name', $oldField->getName()))
            ->andWhere(Criteria::expr()->eq('type', $oldField->getType()))
            ->setMaxResults(1);
        $field = $destinationNodeType->getFields()->matching($criteria)->first();
        return $field ? $field : null;
    }

    /**
     * Warning, this method DOES NOT flush entityManager at the end.
     *
     * @param Node     $node
     * @param NodeType $destinationNodeType
     * @param bool     $mock
     *
     * @return Node
     */
    public function transtype(Node $node, NodeType $destinationNodeType, bool $mock = true): Node
    {
        /*
         * Get an association between old fields and new fields
         * to find data that can be transferred during trans-typing.
         */
        $fieldAssociations = [];
        $oldFields = $node->getNodeType()->getFields();

        foreach ($oldFields as $oldField) {
            $matchingField = $this->getMatchingNodeTypeField($oldField, $destinationNodeType);
            if (null !== $matchingField) {
                $fieldAssociations[] = [
                    $oldField, // old type field
                    $matchingField, // new type field
                ];
            }
        }
        $this->logger->debug('Get matching fields');

        $sourceClass = NodeType::getGeneratedEntitiesNamespace() . "\\" . $destinationNodeType->getSourceEntityClassName();

        /*
         * Testing if new nodeSource class is available
         * and cache have been cleared before actually performing
         * trans-type, not to get an orphan node.
         */
        if ($mock) {
            $this->mockTranstype($destinationNodeType);
        }

        /*
         * Perform actual trans-typing
         */
        $existingSources = $node->getNodeSources()->toArray();
        $this->removeOldSources($node, $existingSources);

        /** @var NodesSources $existingSource */
        foreach ($existingSources as $existingSource) {
            $this->doTranstypeSingleSource($node, $existingSource, $sourceClass, $fieldAssociations);
            $this->logger->debug('Transtyped: '.$existingSource->getTranslation()->getLocale());
        }

        $node->setNodeType($destinationNodeType);
        return $node;
    }

    /**
     * @param Node  $node
     * @param array $sources
     */
    protected function removeOldSources(Node $node, array &$sources)
    {
        /** @var NodesSources $existingSource */
        foreach ($sources as $existingSource) {
            // First plan old source deletion.
            $node->removeNodeSources($existingSource);
            $this->getManager()->remove($existingSource);
        }
        // Flush once
        $this->getManager()->flush();
        $this->logger->debug('Removed old sources');
    }

    /**
     * Warning, this method DO NOT flush entityManager at the end.
     *
     * @param Node         $node
     * @param NodesSources $existingSource
     * @param string       $sourceClass
     * @param array        $fieldAssociations
     *
     * @return NodesSources
     */
    protected function doTranstypeSingleSource(
        Node $node,
        NodesSources $existingSource,
        string $sourceClass,
        array &$fieldAssociations
    ): NodesSources {
        /** @var NodesSources $source */
        $source = new $sourceClass($node, $existingSource->getTranslation());
        $this->getManager()->persist($source);
        $source->setTitle($existingSource->getTitle());

        foreach ($fieldAssociations as $fields) {
            /** @var NodeTypeField $oldField */
            $oldField = $fields[0];
            /** @var NodeTypeField $matchingField */
            $matchingField = $fields[1];

            if (!$oldField->isVirtual()) {
                /*
                 * Copy simple data from source to another
                 */
                $setter = $oldField->getSetterName();
                $getter = $oldField->getGetterName();
                $source->$setter($existingSource->$getter());
            } elseif ($oldField->getType() === NodeTypeField::DOCUMENTS_T) {
                /*
                 * Copy documents.
                 */
                $documents = $existingSource->getDocumentsByFieldsWithName($oldField->getName());
                foreach ($documents as $document) {
                    $nsDoc = new NodesSourcesDocuments($source, $document, $matchingField);
                    $this->getManager()->persist($nsDoc);
                    $source->getDocumentsByFields()->add($nsDoc);
                }
            }
        }
        $this->logger->debug('Fill existing data');

        /*
         * Recreate url-aliases too.
         */
        /** @var UrlAlias $urlAlias */
        foreach ($existingSource->getUrlAliases() as $urlAlias) {
            $newUrlAlias = new UrlAlias($source);
            $newUrlAlias->setAlias($urlAlias->getAlias());
            $source->addUrlAlias($newUrlAlias);
            $this->getManager()->persist($newUrlAlias);
        }
        $this->logger->debug('Recreate aliases');

        return $source;
    }

    /**
     * Warning, this method flushes entityManager.
     *
     * @param NodeType $nodeType
     * @throws \InvalidArgumentException If mock fails due to Source class not existing.
     */
    protected function mockTranstype(NodeType $nodeType): void
    {
        $sourceClass = NodeType::getGeneratedEntitiesNamespace() . "\\" . $nodeType->getSourceEntityClassName();
        if (!class_exists($sourceClass)) {
            throw new \InvalidArgumentException($sourceClass . ' node-source class does not exist.');
        }
        $uniqueId = uniqid();
        /*
         * Testing if new nodeSource class is available
         * and cache have been cleared before actually performing
         * transtype, not to get an orphan node.
         */
        $node = new Node();
        $node->setNodeName('testing_before_transtype' . $uniqueId);
        $this->getManager()->persist($node);

        $translation = new Translation();
        $translation->setAvailable(true);
        $translation->setLocale(substr($uniqueId, 0, 10));
        $translation->setName('test' . $uniqueId);
        $this->getManager()->persist($translation);

        /** @var NodesSources $testSource */
        $testSource = new $sourceClass($node, $translation);
        $testSource->setTitle('testing_before_transtype' . $uniqueId);
        $this->getManager()->persist($testSource);
        $this->getManager()->flush();

        // then remove it if OK
        $this->getManager()->remove($testSource);
        $this->getManager()->remove($node);
        $this->getManager()->remove($translation);
        $this->getManager()->flush();
    }
}
