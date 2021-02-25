<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class TranslationTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new Translation();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
