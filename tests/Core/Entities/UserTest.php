<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class UserTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new User();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
