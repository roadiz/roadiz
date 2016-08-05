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
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;

/**
 * NodesSourcesRepositoryTest.
 */
class NodesSourcesRepositoryTest extends DefaultThemeDependentCase
{
    /**
     * @dataProvider findBySearchQueryProvider
     * @param $query
     * @param $expectedClass
     */
    public function testFindBySearchQuery($query, $expectedClass)
    {
        /** @var \RZ\Roadiz\Core\Repositories\NodesSourcesRepository $repository */
        $repository = static::getManager()->getRepository('RZ\Roadiz\Core\Entities\NodesSources');
        $nSources = $repository->findBySearchQuery($query);

        if (null !== $nSources) {
            foreach ($nSources as $key => $source) {
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
        return array(
            array('Propos', 'GeneratedNodeSources\NSPage'),
            array('About', 'GeneratedNodeSources\NSPage'),
        );
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
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
            ->findBySearchQueryAndTranslation($query, $translation);

        if (null !== $nSources) {
            foreach ($nSources as $key => $source) {
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

        return array(
            array('Propos', 'GeneratedNodeSources\NSPage', $english),
            array('About', 'GeneratedNodeSources\NSPage', $english),
        );
    }

    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::runCommand('solr:reindex -n');
    }
}
