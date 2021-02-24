<?php
use RZ\Roadiz\Random\PasswordGenerator;

class PasswordGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider generatePasswordProvider
     * @param $passwordLength
     */
    public function testGeneratePassword($passwordLength)
    {
        $passGen = new PasswordGenerator();
        $pass = $passGen->generatePassword($passwordLength);

        $this->assertEquals($passwordLength, strlen($pass));
    }

    /**
     * @return array
     */
    public function generatePasswordProvider()
    {
        return [
            [5],
            [6],
            [9],
            [12],
        ];
    }
}
