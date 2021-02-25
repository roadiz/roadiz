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
 * @file SolrWrapperTest.php
 * @author Ambroise Maupate
 */

use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use RZ\Roadiz\Tests\KernelDependentCase;
use Solarium\Exception\HttpException;

/**
 * SolrWrapperTest.
 */
class SolrWrapperTest extends KernelDependentCase
{
    private static $entityCollection;

    public function testIndex()
    {
        /** @var \Solarium\Client $solr */
        $solr = $this->get('solr');

        if (null !== $solr) {
            $testTitle = "Roadiz first test";

            // create a ping query
            $ping = $solr->createPing();
            // execute the ping query
            try {
                $result = $solr->ping($ping);
            } catch (\Solarium\Exception\ExceptionInterface $e) {
                $this->markTestSkipped('Solr is not available.');
            } catch (HttpException $e) {
                $this->markTestSkipped('Solr is not available.');
            }

            // get an update query instance
            $update = $solr->createUpdate();

            $document = $update->createDocument();
            $document->id = uniqid(); //or something else suitably unique
            $document->title = $testTitle;
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
            $query->setQuery('title:"' . $testTitle . '"');

            // this executes the query and returns the result
            $resultset = $solr->select($query);

            // Assert
            $this->assertEquals($resultset->getNumFound(), 1);
        } else {
            $this->markTestSkipped('Solr is not available.');
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
    }
    /**
     * Remove test entities.
     */
    public static function tearDownAfterClass(): void
    {
        $solr = static::$kernel->get('solr');

        if (null !== $solr) {
            try {
                // get an update query instance
                $update = $solr->createUpdate();

                // add the delete query and a commit command to the update query
                foreach (static::$entityCollection as $document) {
                    $update->addDeleteById($document->id);
                }

                $update->addCommit();

                // this executes the query and returns the result
                $result = $solr->update($update);
            } catch (SolrServerNotConfiguredException $e) {
            } catch (HttpException $e) {
            }
        }

        parent::tearDownAfterClass();
    }
}
