<?php

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;

class NodesSourcesHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getUrlProvider
     */
    public function testGetUrl($nodeSource, $expectedUrl)
    {
        $generator = new NodesSourcesUrlGenerator(Kernel::getInstance()->getRequest(), $nodeSource);
        $this->assertEquals($generator->getUrl(), $expectedUrl);
    }

    public static function getUrlProvider()
    {
        $sources = array();

        /*
         * Test 1 - regular node
         */
        $n1 = new Node();
        $n1->setNodeName('page');
        $t1 = new Translation();
        $t1->setLocale('fr');
        $t1->setDefaultTranslation(true);
        $t1->setAvailable(true);
        $ns1 = new NodesSources($n1, $t1);

        $sources[] = array($ns1, '/page');

        /*
         * Test 2  - regular node
         */
        $n2 = new Node();
        $n2->setNodeName('page');
        $t2 = new Translation();
        $t2->setLocale('en');
        $t2->setDefaultTranslation(false);
        $t2->setAvailable(true);
        $ns2 = new NodesSources($n2, $t2);

        $sources[] = array($ns2, '/en/page');


        /*
         * Test 3 - home node
         */
        $n3 = new Node();
        $n3->setNodeName('page');
        $n3->setHome(true);
        $t3 = new Translation();
        $t3->setLocale('fr');
        $t3->setDefaultTranslation(true);
        $t3->setAvailable(true);
        $ns3 = new NodesSources($n3, $t3);

        $sources[] = array($ns3, '/');

        /*
         * Test 4 - home node non-default
         */
        $n4 = new Node();
        $n4->setNodeName('page');
        $n4->setHome(true);
        $t4 = new Translation();
        $t4->setLocale('en');
        $t4->setDefaultTranslation(false);
        $t4->setAvailable(true);
        $ns4 = new NodesSources($n4, $t4);

        $sources[] = array($ns4, '/en');


        /*
         * Test 5  - regular node with alias
         */
        $n5 = new Node();
        $n5->setNodeName('page');
        $t5 = new Translation();
        $t5->setLocale('en');
        $t5->setDefaultTranslation(false);
        $t5->setAvailable(true);
        $ns5 = new NodesSources($n5, $t5);
        $a5 = new Urlalias($ns5);
        $a5->setAlias('tralala-en');
        $ns5->getUrlAliases()->add($a5);

        $sources[] = array($ns5, '/tralala-en');

        /*
         * Test 6  - regular node with 1 parent
         */
        $n6 = new Node();
        $n6->setNodeName('other-page');
        $t6 = new Translation();
        $t6->setLocale('en');
        $t6->setDefaultTranslation(true);
        $t6->setAvailable(true);
        $ns6 = new NodesSources($n6, $t6);

        $ns6->getHandler()->setParentNodeSource($ns1);

        $sources[] = array($ns6, '/page/other-page');

        /*
         * Test 7  - regular node with 2 parents
         */
        $n7 = new Node();
        $n7->setNodeName('sub-page');
        $t7 = new Translation();
        $t7->setLocale('en');
        $t7->setDefaultTranslation(true);
        $t7->setAvailable(true);
        $ns7 = new NodesSources($n7, $t7);

        $ns7->getHandler()->setParentNodeSource($ns6);

        $sources[] = array($ns7, '/page/other-page/sub-page');


        /*
         * Test 8  - regular node with 1 parent and 2 alias
         */
        $n8 = new Node();
        $n8->setNodeName('other-page-alias');
        $t8 = new Translation();
        $t8->setLocale('en');
        $t8->setDefaultTranslation(true);
        $t8->setAvailable(true);
        $ns8 = new NodesSources($n8, $t8);

        $a8 = new Urlalias($ns8);
        $a8->setAlias('other-tralala-en');
        $ns8->getUrlAliases()->add($a8);

        $ns8->getHandler()->setParentNodeSource($ns5);

        $sources[] = array($ns8, '/tralala-en/other-tralala-en');

        /*
         * Test 9 - hidden node
         */
        $n9 = new Node();
        $n9->setNodeName('pagehidden');
        $n9->setVisible(false);
        $t9 = new Translation();
        $t9->setLocale('fr');
        $t9->setDefaultTranslation(true);
        $t9->setAvailable(true);
        $ns9 = new NodesSources($n9, $t9);

        $sources[] = array($ns9, '/pagehidden');

        /*
         * Test 10 - regular node with hidden parent
         */
        $n10 = new Node();
        $n10->setNodeName('page-with-hidden-parent');
        $t10 = new Translation();
        $t10->setLocale('fr');
        $t10->setDefaultTranslation(true);
        $t10->setAvailable(true);
        $ns10 = new NodesSources($n10, $t10);
        $ns10->getHandler()->setParentNodeSource($ns9);

        $sources[] = array($ns10, '/page-with-hidden-parent');

        return $sources;
    }
}
