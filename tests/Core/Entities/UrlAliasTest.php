<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class UrlAliasTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new UrlAlias();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
