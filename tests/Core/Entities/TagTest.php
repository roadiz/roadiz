<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class TagTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new Tag();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
