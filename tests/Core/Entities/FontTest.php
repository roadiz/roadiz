<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class FontTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new Font();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
