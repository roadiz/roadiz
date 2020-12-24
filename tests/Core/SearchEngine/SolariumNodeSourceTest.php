<?php

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Exceptions\SolrServerNotAvailableException;
use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use RZ\Roadiz\Core\SearchEngine\SolariumFactoryInterface;
use RZ\Roadiz\Tests\DefaultThemeWithNodesDependentCase;
use Solarium\Exception\HttpException;

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
        } else {
            $this->markTestSkipped('Ipsum Lorem Vehicula node does not exist.');
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
        } else {
            $this->markTestSkipped('Ipsum Lorem Vehicula node does not exist.');
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
        } else {
            $this->markTestSkipped('Ipsum Lorem Vehicula node does not exist.');
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
