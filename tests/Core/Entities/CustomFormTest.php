<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class CustomFormTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new CustomForm();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
