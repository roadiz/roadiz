<?php

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Kernel;

/**
 * SolrWrapperTest.
 */
class SolrWrapperTest extends PHPUnit_Framework_TestCase
{
    private static $entityCollection;

    public function testIndex()
    {
        $solr = Kernel::getService('solr');

        if (null !== $solr) {

            $testTitle = "Renzo first test";

            // create a ping query
            $ping = $solr->createPing();
            // execute the ping query
            try {
                $result = $solr->ping($ping);
            } catch (\Solarium\Exception $e) {
                echo PHP_EOL. 'No Solr server available.';
                return;
            }

            // get an update query instance
            $update = $solr->createUpdate();

            $document = $update->createDocument();
            $document->id =      uniqid(); //or something else suitably unique
            $document->title =   $testTitle;
            $document->content = 'Some content for this wonderful document. Blah blah blah.';

            static::$entityCollection[] = $document;

            // add the documents and a commit command to the update query
            $update->addDocument($document);
            $update->addCommit();

            $result = $solr->update($update);

            /*
             * ==============================
             *
             * Now query the database
             */
            // get a select query instance
            $query = $solr->createSelect();
            $query->setQuery('title:"'.$testTitle.'"');

            // this executes the query and returns the result
            $resultset = $solr->select($query);

            // Assert
            $this->assertEquals($resultset->getNumFound(), 1);
        }

    }

    /**
     * Nothing special to do except init collection
     * array.
     */
    public static function setUpBeforeClass()
    {
        static::$entityCollection = array();
    }
    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass()
    {
        $solr = Kernel::getService('solr');

        if (null !== $solr) {

            // get an update query instance
            $update = $solr->createUpdate();

            // add the delete query and a commit command to the update query
            foreach (static::$entityCollection as $document) {
                $update->addDeleteById($document->id);
            }

            $update->addCommit();

            // this executes the query and returns the result
            $result = $solr->update($update);
        }
    }
}
