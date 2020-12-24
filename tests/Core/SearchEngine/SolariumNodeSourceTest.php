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

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Exceptions\SolrServerNotAvailableException;
use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use RZ\Roadiz\Core\SearchEngine\SolariumFactoryInterface;
use RZ\Roadiz\Tests\DefaultThemeWithNodesDependentCase;
use Solarium\Exception\HttpException;

/**
 * SolariumNodeSourceTest.
 */
class SolariumNodeSourceTest extends DefaultThemeWithNodesDependentCase
{
    private static $entityCollection;
    private static $documentCollection;

    public function testIndex()
    {
        if ($this->get('solr') === null) {
            $this->markTestSkipped('Solr is not available.');
            return;
        }

        $testTitle = "Ipsum Lorem Vehicula";

        $nodeSource = static::getManager()
            ->getRepository('GeneratedNodeSources\NSPage')
            ->findOneBy(array('title' => $testTitle));

        if (null !== $nodeSource) {
            try {
                $solrDoc = $this->get(SolariumFactoryInterface::class)->createWithNodesSources($nodeSource);
                $solrDoc->indexAndCommit();
                static::$documentCollection[] = $solrDoc;

                /*
                 * ==============================
                 *
                 * Now query the database
                 */
                // get a select query instance
                $query = $this->get('solr')->createSelect();
                $query->setQuery('title:"' . $testTitle . '"');

                // this executes the query and returns the result
                $resultset = $this->get('solr')->select($query);

                foreach ($resultset as $document) {
                    // Assert
                    $this->assertEquals($document->node_source_id_i, $nodeSource->getId());
                }
            } catch (SolrServerNotConfiguredException $e) {
                $this->markTestSkipped('Solr is not available.');
            } catch (SolrServerNotAvailableException $e) {
                $this->markTestSkipped('Solr is not available.');
            } catch (HttpException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }
    }

    public function testGetDocumentFromIndex()
    {
        if ($this->get('solr') === null) {
            $this->markTestSkipped('Solr is not available.');
            return;
        }

        $testTitle = "Ipsum Lorem Vehicula";
        /** @var NodesSources $nodeSource */
        $nodeSource = static::getManager()
            ->getRepository('GeneratedNodeSources\NSPage')
            ->findOneBy(array('title' => $testTitle));

        if (null !== $nodeSource) {
            try {
                $solrDoc = $this->get(SolariumFactoryInterface::class)->createWithNodesSources($nodeSource);
                $this->assertTrue($solrDoc->getDocumentFromIndex());
            } catch (SolrServerNotConfiguredException $e) {
                $this->markTestSkipped('Solr is not available.');
            } catch (SolrServerNotAvailableException $e) {
                $this->markTestSkipped('Solr is not available.');
            } catch (HttpException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }
    }

    public function testCleanAndCommit()
    {
        if ($this->get('solr') === null) {
            $this->markTestSkipped('Solr is not available.');
            return;
        }

        $testTitle = "Ipsum Lorem Vehicula";
        /** @var NodesSources $nodeSource */
        $nodeSource = static::getManager()
            ->getRepository('GeneratedNodeSources\NSPage')
            ->findOneBy(array('title' => $testTitle));

        if (null !== $nodeSource) {
            try {
                $solrDoc = $this->get(SolariumFactoryInterface::class)->createWithNodesSources($nodeSource);
                $solrDoc->cleanAndCommit();

                $this->assertFalse($solrDoc->getDocumentFromIndex());
                return;
            } catch (SolrServerNotConfiguredException $e) {
                $this->markTestSkipped('Solr is not available.');
            } catch (SolrServerNotAvailableException $e) {
                $this->markTestSkipped('Solr is not available.');
            } catch (HttpException $e) {
                $this->markTestSkipped($e->getMessage());
            }
        }
    }

    /**
     * Nothing special to do except init collection
     * array.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$entityCollection = array();
        static::$documentCollection = array();
    }

    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass(): void
    {
        try {
            $solr = static::$kernel->get('solr');

            if (null !== $solr) {
                // get an update query instance
                $update = $solr->createUpdate();

                // add the delete query and a commit command to the update query
                foreach (static::$documentCollection as $document) {
                    $document->remove($update);
                }

                $update->addCommit();

                // this executes the query and returns the result
                $solr->update($update);
            }
        } catch (SolrServerNotConfiguredException $e) {
        } catch (HttpException $e) {
        }

        parent::tearDownAfterClass();
    }
}
