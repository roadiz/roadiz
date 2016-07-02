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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use GeneratedNodeSources\NSPage;
use RZ\Roadiz\Console\RoadizApplication;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Events\DataInheritanceEvent;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Input\StringInput;

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
     * @var RoadizApplication
     */
    static $application;

    /**
     * @var EntityManager
     */
    static $entityManager;

    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$application = new RoadizApplication(Kernel::getInstance());
        static::$application->setAutoExit(false);

        static::runCommand('install -n');
        static::runCommand('themes:install -n "Themes\\\\DefaultTheme\\\\DefaultThemeApp"');
        static::runCommand('themes:install -n --data "Themes\\\\DefaultTheme\\\\DefaultThemeApp"');
        static::runCommand('generate:nsentities');
        static::runCommand('orm:schema-tool:update --dump-sql --force');
    }

    /**
     * @param $command
     * @return int
     * @throws \Exception
     */
    protected static function runCommand($command)
    {
        $command = sprintf('%s --quiet --env=test', $command);

        return static::$application->run(new StringInput($command));
    }

    /**
     * @param $title
     * @param Translation $translation
     * @return Node
     */
    protected static function createPageNode($title, Translation $translation)
    {
        $nodeType = static::getManager()
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findOneByName('Page');

        $node = new Node($nodeType);
        $node->setNodeName($title);
        static::getManager()->persist($node);

        $ns = new NSPage($node, $translation);
        $ns->setTitle($title);
        static::getManager()->persist($ns);

        $node->addNodeSources($ns);

        return $node;
    }

    /**
     * @param $title
     * @param Translation $translation
     * @return Tag
     */
    protected static function createTag($title, Translation $translation)
    {
        $tag = new Tag();
        $tag->setTagName($title);
        static::getManager()->persist($tag);

        $tt = new TagTranslation($tag, $translation);
        $tt->setName($title);
        static::getManager()->persist($tt);

        $tag->getTranslatedTags()->add($tt);

        return $tag;
    }

    /**
     * @return EntityManager
     */
    public static function getManager()
    {
        if (static::$entityManager === null) {
            $config = Kernel::getService('config');
            $emConfig = Kernel::getService('em.config');
            static::$entityManager = EntityManager::create($config["doctrine"], $emConfig);
            $evm = static::$entityManager->getEventManager();

            $prefix = isset($c['config']['doctrine']['prefix']) ? $c['config']['doctrine']['prefix'] : '';

            /*
             * Create dynamic discriminator map for our Node system
             */
            $evm->addEventListener(
                Events::loadClassMetadata,
                new DataInheritanceEvent($prefix)
            );
        }

        return static::$entityManager;
    }
}
