<?php
/**
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
 *
 * @file DomHandlerTest.php
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Utils\DomHandler;

/**
 *
 */
class DomHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getExternalStylesProvider
     * @param $input
     * @param $expected
     */
    public function testGetExternalStyles($input, $expected)
    {
        // Assert
        $this->assertEquals($expected, DomHandler::getExternalStylesFiles($input));
    }

    public function getExternalStylesProvider()
    {
        return array(
            array('<!DOCTYPE html>
<html>
  <head>
    <title>Bootstrap 101 Template</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
  </head>
  <body>
    <h1>Hello, world!</h1>
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>', ['css/bootstrap.min.css']),
            array('<!DOCTYPE html>
<html>
  <head>
    <title>Bootstrap 101 Template</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link  rel="stylesheet"   href=\'css/bootstrap.min.css\'   media="screen" />
    <link href=\'css/style.min.css\' rel="stylesheet" media="screen" />
    <link href=\'img/favicon.ico\' rel="favicon" />
  </head>
  <body>
    <h1>Hello, world!</h1>
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>', ['css/bootstrap.min.css', 'css/style.min.css']),
        );
    }

    /**
     * @dataProvider replaceExternalStylesheetsWithStyleProvider
     * @param $input
     * @param $style
     * @param $expected
     */
    public function testReplaceExternalStylesheetsWithStyle($input, $style, $expected)
    {
        // Assert
        $this->assertXmlStringEqualsXmlString($expected, DomHandler::replaceExternalStylesheetsWithStyle($input, $style));
    }

    public function replaceExternalStylesheetsWithStyleProvider()
    {
        return array(
            array('<!DOCTYPE html>
<html>
  <head>
    <title>Bootstrap 101 Template</title>
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
  </head>
  <body>
    <h1>Hello, world!</h1>
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>', 'body { width:300px; }', '<!DOCTYPE html>
<html>
  <head>
    <title>Bootstrap 101 Template</title>
    <!-- Bootstrap -->
    <style type="text/css">body { width:300px; }</style>
  </head>
  <body>
    <h1>Hello, world!</h1>
    <script src="http://code.jquery.com/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>', ),
        );
    }
}
