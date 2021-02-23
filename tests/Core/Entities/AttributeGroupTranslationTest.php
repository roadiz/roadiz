<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\AttributeGroupTranslation;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class AttributeGroupTranslationTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new AttributeGroupTranslation();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
