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
 * @file NodesSourcesUrlTest.php
 * @author Ambroise Maupate
 */

use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Tests\SchemaDependentCase;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;

/**
 * Class NodesSourcesUrlTest
 */
class NodesSourcesUrlTest extends SchemaDependentCase
{
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

        static::getManager()->persist($fr);
        static::getManager()->persist($en);
        static::getManager()->flush();
    }


    public function testGetUrl()
    {
        $sources = $this->getUrlProvider();

        foreach ($sources as $index => $sourceTuple) {
            $nodeSource = $sourceTuple[0];
            $expectedUrl = $sourceTuple[1];

            $nsUrlGenerator = new NodesSourcesUrlGenerator(null, $nodeSource);

            /*
             * Test previous syntax
             */
            $this->assertEquals($expectedUrl, $nsUrlGenerator->getUrl());

            /*
             * Test current syntax
             */
            $this->assertEquals($expectedUrl, $this->get('urlGenerator')->generate($nodeSource));
        }
    }


    /**
     * @return array
     */
    public function getUrlProvider()
    {
        $sources = [];

        $fr = static::getManager()
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findOneByLocale('fr');

        $en = static::getManager()
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findOneByLocale('en');

        /*
         * Test 1 - regular node
         */
        $n1 = static::createNode("Page", $fr);
        $n1->setVisible(true);
        $ns1 = $n1->getNodeSources()->first();

        $sources[] = [$ns1, '/page'];

        /*
         * Test 2  - regular node
         */
        $n2 = static::createNode("Page 2", $en);
        $ns2 = $n2->getNodeSources()->first();

        $sources[] = [$ns2, '/en/page-2'];

        /*
         * Test 3 - home node
         */
        $n3 = static::createNode("Page 3", $fr);
        $ns3 = $n3->getNodeSources()->first();
        $n3->setHome(true);

        $sources[] = [$ns3, '/'];

        /*
         * Test 4 - home node non-default
         */
        $n4 = static::createNode("Page 4", $en);
        $ns4 = $n4->getNodeSources()->first();
        $n4->setHome(true);

        $sources[] = [$ns4, '/en'];

        /*
         * Test 5  - regular node with alias
         */
        $n5 = static::createNode("Page 5", $en);
        $ns5 = $n5->getNodeSources()->first();

        $a5 = new UrlAlias($ns5);
        $a5->setAlias('tralala-en');
        static::getManager()->persist($a5);
        $ns5->getUrlAliases()->add($a5);

        $sources[] = [$ns5, '/tralala-en'];

        /*
         * Test 6  - regular node with 1 parent
         */
        $n6 = static::createNode("Page 6", $fr);
        $ns6 = $n6->getNodeSources()->first();
        $n6->setParent($n1);

        $sources[] = [$ns6, '/page/page-6'];

        /*
         * Test 7  - regular node with 2 parents
         */
        $n7 = static::createNode("Page 7", $fr);
        $ns7 = $n7->getNodeSources()->first();
        $n7->setParent($n6);

        $sources[] = [$ns7, '/page/page-6/page-7'];

        /*
         * Test 8  - regular node with 1 parent and 2 alias
         */
        $n8 = static::createNode("Page 8", $en);
        $ns8 = $n8->getNodeSources()->first();
        $n8->setParent($n5);

        $a8 = new Urlalias($ns8);
        $a8->setAlias('other-tralala-en');
        $ns8->getUrlAliases()->add($a8);
        static::getManager()->persist($a8);

        $sources[] = [$ns8, '/tralala-en/other-tralala-en'];

        /*
         * Test 9 - hidden node
         */
        $n9 = static::createNode("hidden-page", $fr);
        $ns9 = $n9->getNodeSources()->first();
        $n9->setVisible(false);

        $sources[] = [$ns9, '/hidden-page'];

        /*
         * Test 10 - regular node with hidden parent
         */
        $n10 = static::createNode("page-with-hidden-parent", $fr);
        $ns10 = $n10->getNodeSources()->first();
        $n10->setParent($n9);

        $sources[] = [$ns10, '/page-with-hidden-parent'];

        static::getManager()->flush();

        return $sources;
    }
}
