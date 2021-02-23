<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class CustomFormFieldTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new CustomFormField();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
