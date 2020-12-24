<?php
declare(strict_types=1);

namespace RZ\Roadiz\Tests;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\ToolsException;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\Node\NodeFactory;
use RZ\Roadiz\Utils\Tag\TagFactory;

/**
 * Class DefaultThemeDependentCase for UnitTest which need EntityManager and some NodeTypes and nodes.
 *
 * Be careful, these tests must be executed on a clear database! Or all data will be lost.
 *
 * @package RZ\Roadiz\Tests
 */
abstract class DefaultThemeDependentCase extends SchemaDependentCase
{
    /**
     * @throws ToolsException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::runCommand('install');
        static::runCommand('themes:install --data "/Themes/DefaultTheme/DefaultThemeApp"');
        static::runCommand('generate:nsentities');
        static::runCommand('orm:schema-tool:update --dump-sql --force');
        static::runCommand('cache:clear');
        static::runCommand('themes:install --nodes "/Themes/DefaultTheme/DefaultThemeApp"');
        static::$kernel->get('nodeTypesBag')->reset();
        static::$kernel->get('settingsBag')->reset();
    }

    /**
     * No need to persist
     *
     * @param string      $title
     * @param Translation $translation
     * @param Node|null   $parent
     *
     * @return Node
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    protected static function createPageNode(string $title, Translation $translation, Node $parent = null): Node
    {
        /** @var NodeFactory $nodeFactory */
        $nodeFactory = static::$kernel->getContainer()->offsetGet(NodeFactory::class);
        $nodeType = static::getManager()->getRepository(NodeType::class)->findOneByName('Page');
        if (null === $nodeType) {
            throw new EntityNotFoundException('Page node-type does not exist.');
        }
        $node = $nodeFactory->create($title, $nodeType, $translation, null, $parent);

        return $node;
    }

    /**
     * @param string $title
     * @param Translation $translation
     * @return Tag
     */
    protected static function createTag(string $title, Translation $translation): Tag
    {
        /** @var TagFactory $tagFactory */
        $tagFactory = static::$kernel->getContainer()->offsetGet(TagFactory::class);
        return $tagFactory->create($title, $translation);
    }
}
