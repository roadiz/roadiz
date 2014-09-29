<?php

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\SearchEngine\SolariumNodeSource;
use RZ\Renzo\Core\Kernel;


use RZ\Renzo\Core\Exceptions\SolrServerNotAvailableException;
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

        try {

            $solrDoc = new SolariumNodeSource(
                $nodeSource,
                Kernel::getInstance()->getSolrService()
            );

            $result = $solrDoc->indexAndCommit();
            static::$documentCollection[] = $solrDoc;

            /*
             * ==============================
             *
             * Now query the database
             */
            // get a select query instance
            $query = Kernel::getInstance()->getSolrService()->createSelect();
            $query->setQuery('title:"'.$testTitle.'"');

            // this executes the query and returns the result
            $resultset = Kernel::getInstance()->getSolrService()->select($query);

            foreach ($resultset as $document) {
                // Assert
                $this->assertEquals($document->node_source_id_i, $nodeSource->getId());
            }
        } catch (SolrServerNotAvailableException $e){

        }
    }

    public function testGetDocumentFromIndex()
    {
        $testTitle = "Ipsum Lorem Vehicula";

        $nodeSource = Kernel::getService('em')
                        ->getRepository('GeneratedNodeSources\NSPage')
                        ->findOneBy(array('title'=>$testTitle));
        try {
            $solrDoc = new SolariumNodeSource(
                $nodeSource,
                Kernel::getInstance()->getSolrService()
            );

            $this->assertTrue($solrDoc->getDocumentFromIndex());

        } catch (SolrServerNotAvailableException $e){

        }
    }

    public function testCleanAndCommit()
    {
        $testTitle = "Ipsum Lorem Vehicula";

        $nodeSource = Kernel::getService('em')
                        ->getRepository('GeneratedNodeSources\NSPage')
                        ->findOneBy(array('title'=>$testTitle));

        try {

            $solrDoc = new SolariumNodeSource(
                $nodeSource,
                Kernel::getInstance()->getSolrService()
            );

            $solrDoc->cleanAndCommit();

            $this->assertFalse($solrDoc->getDocumentFromIndex());
        } catch (SolrServerNotAvailableException $e){

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
        $solr = Kernel::getInstance()->getSolrService();

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
    }
}
