<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;

final class FolderTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /*
     * Test empty object serialization
     */
    public function testSerialize()
    {
        $a = new Folder();
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }
}
