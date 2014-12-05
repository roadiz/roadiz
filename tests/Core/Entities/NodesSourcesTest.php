<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesSourcesTest.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\NodesSources;
/**
 * Test NodesSources features
 */
class NodesSourcesTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testNodesSources()
    {
        $n = new Node();
        $t = new Translation();


        // Arrange
        $a = new NodesSources($n, $t);
        $a->setTitle('Test node');


        // Assert
        $this->assertNotNull($a);
    }

}
