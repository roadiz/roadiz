<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class DocumentTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new Document();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
