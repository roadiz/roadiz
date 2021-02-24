<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class LogTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new Log(Log::ERROR, 'Test error');
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
