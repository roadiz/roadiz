<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Core\Serializers\NodeJsonSerializer;

/**
 * @package RZ\Roadiz\CMS\Importers
 */
class NodesImporter implements ImporterInterface, EntityImporterInterface
{
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $entityClass): bool
    {
        return $entityClass === Node::class;
    }

    /**
     * @inheritDoc
     */
    public function import(string $serializedData): bool
    {
        static::$usedTranslations = [];
        $objectManager = $this->managerRegistry->getManagerForClass(Node::class);
        if (null === $objectManager) {
            throw new \RuntimeException('No manager found for ' . Node::class);
        }
        $serializer = new NodeJsonSerializer($objectManager);
        $nodes = $serializer->deserialize($serializedData);

        try {
            foreach ($nodes as $node) {
                static::browseTree($node, $objectManager);
            }
            $objectManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new EntityAlreadyExistsException($e->getMessage());
        }

        return true;
    }


    protected static $usedTranslations;

    /**
     * Import a Json file (.rzt) containing node and node source.
     *
     * @param string $serializedData
     * @param ObjectManager $objectManager
     * @param HandlerFactoryInterface $handlerFactory
     * @return bool
     * @deprecated
     */
    public static function importJsonFile($serializedData, ObjectManager $objectManager, HandlerFactoryInterface $handlerFactory)
    {
        static::$usedTranslations = [];
        $serializer = new NodeJsonSerializer($objectManager);
        $nodes = $serializer->deserialize($serializedData);

        try {
            foreach ($nodes as $node) {
                static::browseTree($node, $objectManager);
            }
            $objectManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new EntityAlreadyExistsException($e->getMessage());
        }

        return true;
    }

    /**
     * @param Node $node
     * @param ObjectManager $objectManager
     * @return null|Node
     * @throws EntityAlreadyExistsException
     */
    protected static function browseTree(Node $node, ObjectManager $objectManager)
    {
        /*
         * Test if node already exists against its nodeName
         */
        /** @var NodeRepository $nodeRepo */
        $nodeRepo = $objectManager->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true);
        $existing = $nodeRepo->findOneByNodeName($node->getNodeName());
        if (null !== $existing) {
            throw new EntityAlreadyExistsException('"' . $node->getNodeName() . '" already exists.');
        }

        /** @var Node $child */
        foreach ($node->getChildren() as $child) {
            static::browseTree($child, $objectManager);
        }
        $objectManager->persist($node);

        /** @var NodesSources $nodeSource */
        foreach ($node->getNodeSources() as $nodeSource) {
            /** @var Translation|null $trans */
            $trans = $objectManager->getRepository(Translation::class)
                        ->findOneByLocale($nodeSource->getTranslation()->getLocale());

            if (null === $trans &&
                !empty(static::$usedTranslations[$nodeSource->getTranslation()->getLocale()])) {
                $trans = static::$usedTranslations[$nodeSource->getTranslation()->getLocale()];
            }

            if (null === $trans) {
                $trans = new Translation();
                $trans->setLocale($nodeSource->getTranslation()->getLocale());
                $trans->setName(Translation::$availableLocales[$nodeSource->getTranslation()->getLocale()]);
                $objectManager->persist($trans);

                static::$usedTranslations[$nodeSource->getTranslation()->getLocale()] = $trans;
            }
            $nodeSource->setTranslation($trans);
            foreach ($nodeSource->getUrlAliases() as $alias) {
                $objectManager->persist($alias);
            }

            $objectManager->persist($nodeSource);
        }

        return $node;
    }
}
