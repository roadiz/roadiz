<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file KernelDependentCase.php
 * @author Ambroise Maupate
 */
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
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::runCommand('install');
        static::runCommand('themes:install --data "/Themes/DefaultTheme/DefaultThemeApp"');
        static::runCommand('generate:nsentities');
        static::runCommand('orm:schema-tool:update --dump-sql --force');
        static::runCommand('cache:clear');
        static::runCommand('themes:install --nodes "/Themes/DefaultTheme/DefaultThemeApp"');
    }

    /**
     * No need to persist
     *
     * @param $title
     * @param Translation $translation
     * @param Node|null $parent
     * @return Node
     */
    protected static function createPageNode($title, Translation $translation, Node $parent = null): Node
    {
        /** @var NodeFactory $nodeFactory */
        $nodeFactory = static::getContainer()->offsetGet(NodeFactory::class);
        $nodeType = static::getContainer()->offsetGet('nodeTypesBag')->get('Page');
        if (null === $nodeType) {
            throw new EntityNotFoundException('Page node-type does not exist.');
        }
        $nodeFactory->create($title, $nodeType, $translation);

        $node = $nodeFactory->create($title, $nodeType, $translation);

        if (null !== $parent) {
            $parent->addChild($node);
        }

        return $node;
    }

    /**
     * @param $title
     * @param Translation $translation
     * @return Tag
     */
    protected static function createTag($title, Translation $translation): Tag
    {
        /** @var TagFactory $tagFactory */
        $tagFactory = static::getContainer()->offsetGet(TagFactory::class);
        return $tagFactory->create($title, $translation);
    }
}
