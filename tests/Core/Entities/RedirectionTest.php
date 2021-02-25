<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Redirection;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class RedirectionTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new Redirection();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
