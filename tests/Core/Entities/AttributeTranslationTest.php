<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\AttributeTranslation;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class AttributeTranslationTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new AttributeTranslation();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
