<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file SettingTest.php
 * @author Ambroise Maupate
 */

use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Tests\SchemaDependentCase;

class SettingTest extends SchemaDependentCase
{
    /**
     * @dataProvider settingsProvider
     * @param $name
     * @param $expected
     */
    public function testGetValue($name, $expected)
    {
        $value = static::getManager()
            ->getRepository(Setting::class)
            ->getValue($name);

        // Assert
        $this->assertEquals($expected, $value);
    }

    /**
     * @return array
     */
    public static function settingsProvider()
    {
        return [
            ["test_de_setting", "1"],
            ["test_de_setting_c", true],
            ["test_de_setting_c_c", "j-aime-les-sushis"],
            ["test_de_setting_c_c_c", "j-ai\"''me-les-sushis"],
            ["test_de_setting_c_c_c_c", "j-ai\"''me-les-suéàçshis"],
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $settings = static::settingsProvider();

        foreach ($settings as $setting) {
            $s = new Setting();
            $s->setName($setting[0]);
            $s->setValue($setting[1]);
            static::getManager()->persist($s);
        }

        static::getManager()->flush();
    }
}
