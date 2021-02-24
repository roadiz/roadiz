<?php
declare(strict_types=1);

use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Tests\SerializedEntityTestTrait;
use Symfony\Component\String\UnicodeString;

/**
 * Test Role features
 */
class RoleTest extends \PHPUnit\Framework\TestCase
{
    use SerializedEntityTestTrait;

    /**
     * Test empty object serialization
     * @dataProvider roleNameProvider
     * @param $roleName
     */
    public function testSerialize($roleName)
    {
        // Arrange
        $a = new Role($roleName);
        // Assert
        $this->assertJson($this->getSerializer()->serialize($a, 'json'));
    }

    /**
     * @dataProvider snakeProvider
     * @param string $input
     * @param string $expected
     */
    public function testSnake($input, $expected)
    {
        $output = (new UnicodeString($input))
            ->ascii()
            ->folded()
            ->snake()
            ->upper()
            ->toString()
        ;
        $this->assertEquals($expected, $output);
    }

    public function snakeProvider()
    {
        return [
            ["ROLE_TEST", "ROLE_TEST"],
            ["ROLE_TEST_TEST", "ROLE_TEST_TEST"],
            ["ROLE_ASDF_AASDF", "ROLE_ASDF_AASDF"],
        ];
    }

    /**
     * @dataProvider roleNameProvider
     * @param $roleName
     * @param $expected
     */
    public function testRoleName($roleName, $expected)
    {
        $a = new Role($roleName);
        $this->assertEquals($expected, $a->getRole());
    }

    public function roleNameProvider()
    {
        return [
            ["role___àsdfasdf", "ROLE_ASDFASDF"],
            ["asdf ààsdf", "ROLE_ASDF_AASDF"],
            ["asdfasdf", "ROLE_ASDFASDF"],
            ["role___test", "ROLE_TEST"],
            ["role___test", "ROLE_TEST"],
            ["ROLE_TEST", "ROLE_TEST"],
            ["tèst tèst", "ROLE_TEST_TEST"],
            ["ROLE_TEST_TEST", "ROLE_TEST_TEST"],
            ["role_test_test", "ROLE_TEST_TEST"],
            ["ROLE_ASDF_AASDF", "ROLE_ASDF_AASDF"],
            ["role_asdf_aasdf", "ROLE_ASDF_AASDF"],
        ];
    }
}
