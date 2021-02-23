<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Attribute;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class AttributeTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new Attribute();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
