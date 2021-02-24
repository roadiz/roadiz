<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class GroupTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new Group();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
