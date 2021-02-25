<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

class NodesSourcesTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $n = new Node();
        $t = new Translation();
        $a = new NodesSources($n, $t);
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }

    public function testNodesSources()
    {
        $n = new Node();
        $t = new Translation();
        // Arrange
        $a = new NodesSources($n, $t);
        $a->setTitle('Test node');
        // Assert
        $this->assertNotNull($a);

        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
        $this->assertJson($this->getSerializer()->serialize($n, 'json'));
        $this->assertJson($this->getSerializer()->serialize($t, 'json'));
    }
}
