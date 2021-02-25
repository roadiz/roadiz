<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\UserLogEntry;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class UserLogEntryTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new UserLogEntry();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
