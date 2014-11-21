<?php

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\SearchEngine\SolariumNodeSource;
use RZ\Roadiz\Core\Kernel;

use RZ\Roadiz\Core\Exceptions\SolrServerNotAvailableException;
use Solarium\Exception\HttpException;
/**
 * SolariumNodeSourceTest.
 */
class SolariumNodeSourceTest extends PHPUnit_Framework_TestCase
{
    private static $entityCollection;
    private static $documentCollection;

    public function testIndex() {
        $testTitle = "Ipsum Lorem Vehicula";

        $nodeSource = Kernel::getService('em')
                        ->getRepository('GeneratedNodeSources\NSPage')
                        ->findOneBy(array('title'=>$testTitle));

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
                $query->setQuery('title:"'.$testTitle.'"');

                // this executes the query and returns the result
                $resultset = Kernel::getService('solr')->select($query);

                foreach ($resultset as $document) {
                    // Assert
                    $this->assertEquals($document->node_source_id_i, $nodeSource->getId());
                }
            } catch (SolrServerNotAvailableException $e){
                echo PHP_EOL. 'No Solr server available.'.PHP_EOL;
                return;
            }
        }

    }

    public function testGetDocumentFromIndex()
    {
        $testTitle = "Ipsum Lorem Vehicula";

        $nodeSource = Kernel::getService('em')
                        ->getRepository('GeneratedNodeSources\NSPage')
                        ->findOneBy(array('title'=>$testTitle));

        if (null !== $nodeSource) {
            try {
                $solrDoc = new SolariumNodeSource(
                    $nodeSource,
                    Kernel::getService('solr')
                );

                $this->assertTrue($solrDoc->getDocumentFromIndex());

            } catch (SolrServerNotAvailableException $e){
                echo PHP_EOL. 'No Solr server available.'.PHP_EOL;
                return;
            }
        }
    }

    public function testCleanAndCommit()
    {
        $testTitle = "Ipsum Lorem Vehicula";

        $nodeSource = Kernel::getService('em')
                        ->getRepository('GeneratedNodeSources\NSPage')
                        ->findOneBy(array('title'=>$testTitle));

        if (null !== $nodeSource) {
            try {

                $solrDoc = new SolariumNodeSource(
                    $nodeSource,
                    Kernel::getService('solr')
                );

                $solrDoc->cleanAndCommit();

                $this->assertFalse($solrDoc->getDocumentFromIndex());

            } catch (SolrServerNotAvailableException $e){

            } catch(HttpException $e) {
                echo PHP_EOL. 'No Solr server available.'.PHP_EOL;
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
        } catch(HttpException $e) {
            echo PHP_EOL. 'No Solr server available.'.PHP_EOL;
            return;
        }
    }
}
