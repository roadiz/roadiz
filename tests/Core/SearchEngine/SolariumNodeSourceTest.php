<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file SolariumNodeSourceTest.php
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Exceptions\SolrServerNotAvailableException;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\SearchEngine\SolariumNodeSource;
use RZ\Roadiz\Tests\KernelDependentCase;
use Solarium\Exception\HttpException;

/**
 * SolariumNodeSourceTest.
 */
class SolariumNodeSourceTest extends KernelDependentCase
{
    private static $entityCollection;
    private static $documentCollection;

    public function testIndex()
    {
        $testTitle = "Ipsum Lorem Vehicula";

        $nodeSource = Kernel::getService('em')
            ->getRepository('GeneratedNodeSources\NSPage')
            ->findOneBy(array('title' => $testTitle));

        if (null !== $nodeSource) {
            try {
                $solrDoc = new SolariumNodeSource(
                    $nodeSource,
                    Kernel::getService('solr')
                );

                $result = $solrDoc->indexAndCommit();
                static::$documentCollection[] = $solrDoc;

                /*
                 * ==============================
                 *
                 * Now query the database
                 */
                // get a select query instance
                $query = Kernel::getService('solr')->createSelect();
                $query->setQuery('title:"' . $testTitle . '"');

                // this executes the query and returns the result
                $resultset = Kernel::getService('solr')->select($query);

                foreach ($resultset as $document) {
                    // Assert
                    $this->assertEquals($document->node_source_id_i, $nodeSource->getId());
                }
            } catch (SolrServerNotAvailableException $e) {
                return;
            }
        }

    }

    public function testGetDocumentFromIndex()
    {
        $testTitle = "Ipsum Lorem Vehicula";

        $nodeSource = Kernel::getService('em')
            ->getRepository('GeneratedNodeSources\NSPage')
            ->findOneBy(array('title' => $testTitle));

        if (null !== $nodeSource) {
            try {
                $solrDoc = new SolariumNodeSource(
                    $nodeSource,
                    Kernel::getService('solr')
                );

                $this->assertTrue($solrDoc->getDocumentFromIndex());

            } catch (SolrServerNotAvailableException $e) {
                return;
            }
        }
    }

    public function testCleanAndCommit()
    {
        $testTitle = "Ipsum Lorem Vehicula";

        $nodeSource = Kernel::getService('em')
            ->getRepository('GeneratedNodeSources\NSPage')
            ->findOneBy(array('title' => $testTitle));

        if (null !== $nodeSource) {
            try {
                $solrDoc = new SolariumNodeSource(
                    $nodeSource,
                    Kernel::getService('solr')
                );

                $solrDoc->cleanAndCommit();

                $this->assertFalse($solrDoc->getDocumentFromIndex());

            } catch (SolrServerNotAvailableException $e) {
            } catch (HttpException $e) {
                return;
            }
        }
    }

    /**
     * Nothing special to do except init collection
     * array.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$entityCollection = array();
        static::$documentCollection = array();
    }

    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass()
    {
        try {
            $solr = Kernel::getService('solr');

            if (null !== $solr) {
                // get an update query instance
                $update = $solr->createUpdate();

                // add the delete query and a commit command to the update query
                foreach (static::$documentCollection as $document) {
                    $document->remove($update);
                }

                $update->addCommit();

                // this executes the query and returns the result
                $result = $solr->update($update);
            }
        } catch (HttpException $e) {
            return;
        }

        parent::tearDownAfterClass();
    }
}
