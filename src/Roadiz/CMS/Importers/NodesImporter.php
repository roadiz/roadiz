<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;
use RZ\Roadiz\Core\Serializers\NodeJsonSerializer;

/**
 * Class NodesImporter
 *
 * @package RZ\Roadiz\CMS\Importers
 */
class NodesImporter implements ImporterInterface, EntityImporterInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * NodesImporter constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
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
        /** @var EntityManager $em */
        $em = $this->get('em');

        static::$usedTranslations = [];
        $serializer = new NodeJsonSerializer($em);
        $nodes = $serializer->deserialize($serializedData);

        try {
            foreach ($nodes as $node) {
                static::browseTree($node, $em);
            }
            $em->flush();
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
     * @param EntityManager $em
     * @param HandlerFactoryInterface $handlerFactory
     * @return bool
     * @deprecated
     */
    public static function importJsonFile($serializedData, EntityManager $em, HandlerFactoryInterface $handlerFactory)
    {
        static::$usedTranslations = [];
        $serializer = new NodeJsonSerializer($em);
        $nodes = $serializer->deserialize($serializedData);

        try {
            foreach ($nodes as $node) {
                static::browseTree($node, $em);
            }
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new EntityAlreadyExistsException($e->getMessage());
        }

        return true;
    }

    /**
     * @param Node $node
     * @param EntityManager $em
     * @return null|Node
     * @throws EntityAlreadyExistsException
     */
    protected static function browseTree(Node $node, EntityManager $em)
    {
        /*
         * Test if node already exists against its nodeName
         */
        $existing = $em->getRepository(Node::class)
                       ->setDisplayingNotPublishedNodes(true)
                       ->findOneByNodeName($node->getNodeName());
        if (null !== $existing) {
            throw new EntityAlreadyExistsException('"' . $node->getNodeName() . '" already exists.');
        }

        /** @var Node $child */
        foreach ($node->getChildren() as $child) {
            static::browseTree($child, $em);
        }
        $em->persist($node);

        /** @var NodesSources $nodeSource */
        foreach ($node->getNodeSources() as $nodeSource) {
            /** @var Translation|null $trans */
            $trans = $em->getRepository(Translation::class)
                        ->findOneByLocale($nodeSource->getTranslation()->getLocale());

            if (null === $trans &&
                !empty(static::$usedTranslations[$nodeSource->getTranslation()->getLocale()])) {
                $trans = static::$usedTranslations[$nodeSource->getTranslation()->getLocale()];
            }

            if (null === $trans) {
                $trans = new Translation();
                $trans->setLocale($nodeSource->getTranslation()->getLocale());
                $trans->setName(Translation::$availableLocales[$nodeSource->getTranslation()->getLocale()]);
                $em->persist($trans);

                static::$usedTranslations[$nodeSource->getTranslation()->getLocale()] = $trans;
            }
            $nodeSource->setTranslation($trans);
            foreach ($nodeSource->getUrlAliases() as $alias) {
                $em->persist($alias);
            }

            $em->persist($nodeSource);
        }

        return $node;
    }
}
