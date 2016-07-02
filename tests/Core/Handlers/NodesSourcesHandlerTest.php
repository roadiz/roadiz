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
 * @file NodesSourcesHandlerTest.php
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Tests\SchemaDependentCase;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;

class NodesSourcesHandlerTest extends SchemaDependentCase
{
    /**
     *
     */
    public function testGetUrl()
    {
        $sources = $this->getUrlProvider();

        foreach ($sources as $sourceTuple) {
            $nodeSource = $sourceTuple[0];
            $expectedUrl = $sourceTuple[1];
            $generator = new NodesSourcesUrlGenerator(Kernel::getService('request'), $nodeSource);

            $this->assertEquals($generator->getUrl(), $expectedUrl);
        }
    }

    /**
     * Nothing special to do except init collection
     * array.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $fr = new Translation();
        $fr->setName('fr');
        $fr->setLocale('fr');
        $fr->setDefaultTranslation(true);
        $fr->setAvailable(true);

        $en = new Translation();
        $en->setName('en');
        $en->setLocale('en');
        $en->setDefaultTranslation(false);
        $en->setAvailable(true);

        Kernel::getService('em')->persist($fr);
        Kernel::getService('em')->persist($en);
        Kernel::getService('em')->flush();
    }


    /**
     * @return array
     */
    private function getUrlProvider()
    {
        $sources = array();

        $fr = Kernel::getService("em")
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findOneByLocale('fr');

        $en = Kernel::getService("em")
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findOneByLocale('en');

        /*
         * Test 1 - regular node
         */
        $n1 = new Node();
        $n1->setNodeName('page');
        Kernel::getService('em')->persist($n1);

        $ns1 = new NodesSources($n1, $fr);
        Kernel::getService('em')->persist($ns1);

        $sources[] = array($ns1, '/page');

        /*
         * Test 2  - regular node
         */
        $n2 = new Node();
        $n2->setNodeName('Page 2');
        Kernel::getService('em')->persist($n2);

        $ns2 = new NodesSources($n2, $en);
        Kernel::getService('em')->persist($ns2);

        $sources[] = array($ns2, '/en/page-2');

        /*
         * Test 3 - home node
         */
        $n3 = new Node();
        $n3->setNodeName('Page 3');
        $n3->setHome(true);
        Kernel::getService('em')->persist($n3);

        $ns3 = new NodesSources($n3, $fr);
        Kernel::getService('em')->persist($ns3);

        $sources[] = array($ns3, '/');

        /*
         * Test 4 - home node non-default
         */
        $n4 = new Node();
        $n4->setNodeName('Page 4');
        $n4->setHome(true);
        Kernel::getService('em')->persist($n4);

        $ns4 = new NodesSources($n4, $en);
        Kernel::getService('em')->persist($ns4);

        $sources[] = array($ns4, '/en');

        /*
         * Test 5  - regular node with alias
         */
        $n5 = new Node();
        $n5->setNodeName('Page 5');
        Kernel::getService('em')->persist($n5);

        $ns5 = new NodesSources($n5, $en);
        Kernel::getService('em')->persist($ns5);

        $a5 = new Urlalias($ns5);
        $a5->setAlias('tralala-en');
        Kernel::getService('em')->persist($a5);

        $ns5->getUrlAliases()->add($a5);

        $sources[] = array($ns5, '/tralala-en');

        /*
         * Test 6  - regular node with 1 parent
         */
        $n6 = new Node();
        $n6->setNodeName('Page 6');
        Kernel::getService('em')->persist($n6);

        $ns6 = new NodesSources($n6, $fr);
        Kernel::getService('em')->persist($ns6);

        $ns6->getHandler()->setParentNodeSource($ns1);

        $sources[] = array($ns6, '/page/page-6');

        /*
         * Test 7  - regular node with 2 parents
         */
        $n7 = new Node();
        $n7->setNodeName('Page 7');
        Kernel::getService('em')->persist($n7);

        $ns7 = new NodesSources($n7, $fr);
        Kernel::getService('em')->persist($ns7);

        $ns7->getHandler()->setParentNodeSource($ns6);

        $sources[] = array($ns7, '/page/page-6/page-7');

        /*
         * Test 8  - regular node with 1 parent and 2 alias
         */
        $n8 = new Node();
        $n8->setNodeName('Page 8');
        Kernel::getService('em')->persist($n8);
        $ns8 = new NodesSources($n8, $fr);
        Kernel::getService('em')->persist($ns8);

        $a8 = new Urlalias($ns8);
        $a8->setAlias('other-tralala-en');
        $ns8->getUrlAliases()->add($a8);
        Kernel::getService('em')->persist($a8);

        $ns8->getHandler()->setParentNodeSource($ns5);

        $sources[] = array($ns8, '/tralala-en/other-tralala-en');

        /*
         * Test 9 - hidden node
         */
        $n9 = new Node();
        $n9->setNodeName('Hidden page');
        $n9->setVisible(false);
        Kernel::getService('em')->persist($n9);

        $ns9 = new NodesSources($n9, $fr);
        Kernel::getService('em')->persist($ns9);

        $sources[] = array($ns9, '/hidden-page');

        /*
         * Test 10 - regular node with hidden parent
         */
        $n10 = new Node();
        $n10->setNodeName('page-with-hidden-parent');
        Kernel::getService('em')->persist($n10);

        $ns10 = new NodesSources($n10, $fr);
        $ns10->getHandler()->setParentNodeSource($ns9);
        Kernel::getService('em')->persist($ns10);

        $sources[] = array($ns10, '/page-with-hidden-parent');

        Kernel::getService('em')->flush();

        return $sources;
    }
}
