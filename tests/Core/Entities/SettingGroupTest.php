<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class SettingGroupTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new SettingGroup();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
