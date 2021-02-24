<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\AttributeValue;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class AttributeValueTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new AttributeValue();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
