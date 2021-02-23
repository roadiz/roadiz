<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\AttributeGroup;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class AttributeGroupTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new AttributeGroup();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
