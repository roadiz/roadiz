<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 *
 * @file NodesSourcesRepositoryTest.php
 * @author Ambroise Maupate
 */

use GeneratedNodeSources\NSPage;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\NodesSourcesRepository;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;

/**
 * NodesSourcesRepositoryTest.
 */
class NodesSourcesRepositorySearchTest extends DefaultThemeDependentCase
{
    /**
     * @dataProvider findBySearchQueryProvider
     * @param $query
     * @param $expectedClass
     */
    public function testFindBySearchQuery($query, $expectedClass)
    {
        /** @var NodesSourcesRepository $repository */
        $repository = static::getManager()
            ->getRepository(NodesSources::class)
            ->setDisplayingAllNodesStatuses(true)
            ->setDisplayingNotPublishedNodes(true)
        ;
        $nSources = $repository->findBySearchQuery($query);

        if (null !== $nSources || count($nSources) > 0) {
            foreach ($nSources as $source) {
                $this->assertEquals($expectedClass, get_class($source));
            }
        } else {
            $this->markTestSkipped('No nodes are available for this search.');
        }
    }
    /**
     * @return array
     */
    public static function findBySearchQueryProvider()
    {
        return [
            ['Propos', NSPage::class],
            ['About', NSPage::class],
        ];
    }

    /**
     * @dataProvider findBySearchQueryAndTranslationProvider
     * @param $query
     * @param $expectedClass
     * @param Translation $translation
     */
    public function testFindBySearchQueryAndTranslation($query, $expectedClass, Translation $translation)
    {
        $nSources = static::getManager()
            ->getRepository(NodesSources::class)
            ->setDisplayingAllNodesStatuses(true)
            ->setDisplayingNotPublishedNodes(true)
            ->findBySearchQueryAndTranslation($query, $translation);

        if (null !== $nSources || count($nSources) > 0) {
            foreach ($nSources as $source) {
                $this->assertEquals($expectedClass, get_class($source));
            }
        } else {
            $this->markTestSkipped('No nodes are available for this search.');
        }
    }
    /**
     * @return array
     */
    public static function findBySearchQueryAndTranslationProvider()
    {
        $english = new Translation();
        $english->setLocale('en_GB');

        return [
            ['Propos', NSPage::class, $english],
            ['About', NSPage::class, $english],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::runCommand('themes:install --nodes "/Themes/DefaultTheme/DefaultThemeApp"');
        static::runCommand('solr:reindex');
    }
}
