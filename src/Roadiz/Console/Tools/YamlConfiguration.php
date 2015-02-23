<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file YamlConfiguration.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console\Tools;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * YamlConfiguration class
 */
class YamlConfiguration extends Configuration
{
    /**
     * Load default configuration file
     *
     * @return boolean
     */
    public function load()
    {
        // Try to load existant configuration
        return $this->loadFromFile(ROADIZ_ROOT . '/conf/config.yml');
    }

    /**
     * @param string $file Absolute path to conf file
     *
     * @return boolean
     */
    public function loadFromFile($file)
    {
        if (file_exists($file)) {
            try {
                $yaml = new Parser();
                $conf = $yaml->parse(file_get_contents($file));
                $this->setConfiguration($conf);

                return true;

            } catch (ParseException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    public function writeConfiguration()
    {
        $writePath = ROADIZ_ROOT . '/conf/config.yml';

        if (file_exists($writePath)) {
            unlink($writePath);
        }

        try {
            $dumper = new Dumper();
            $yaml = $dumper->dump($this->getConfiguration(), 2);

            file_put_contents($writePath, $yaml);
            return true;
        } catch (ParseException $e) {
            return false;
        }
    }
}
