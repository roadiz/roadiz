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
 * @file DomHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Utils;

/**
 * Description.
 */
class DomHandler
{
    /**
     * Extract stylesheet filenames from an HTML string.
     *
     * @param string $dom
     *
     * @return array
     */
    public static function getExternalStylesFiles($dom)
    {
        $doc = new \DOMDocument();
        $doc->loadHTML($dom);
        $elements = $doc->getElementsByTagName('link');

        $fileNames = [];

        foreach ($elements as $node) {
            $href = $node->attributes->getNamedItem('href');
            $rel = $node->attributes->getNamedItem('rel');

            if ($node->hasAttributes() &&
                null !== $href &&
                null !== $rel) {
                if ($rel->value == "stylesheet") {
                    $fileNames[] = $href->value;
                }
            }
        }

        return $fileNames;
    }

    /**
     * Get CSS code from existing stylesheet files in HTML code.
     *
     * If file path is relative, search with ROADIZ_ROOT path prefix.
     *
     * @param string $dom
     *
     * @return string Concatained CSS code
     */
    public static function getExternalStyles($dom)
    {
        $cssFiles = static::getExternalStylesFiles($dom);

        // Concat all css-file in one string
        $cssContent = "";
        foreach ($cssFiles as $css) {
            if (file_exists($css)) {
                $cssContent .= file_get_contents($css) . PHP_EOL;
            } elseif (file_exists(ROADIZ_ROOT . $css)) {
                $cssContent .= file_get_contents(ROADIZ_ROOT . $css) . PHP_EOL;
            }
        }

        return $cssContent;
    }

    /**
     * Replace all external stylesheet <link> tags with a single <style> tag
     * with all concatained CSS inside.
     *
     * @param string $dom
     * @param string $style
     *
     * @return string
     */
    public static function replaceExternalStylesheetsWithStyle($dom, $style)
    {
        $doc = new \DOMDocument();
        $doc->loadHTML($dom);

        $elements = $doc->getElementsByTagName('link');
        $head = $doc->getElementsByTagName('head');

        if ($head->length === 1) {
            $headTag = $head->item(0);
            foreach ($elements as $node) {
                $href = $node->attributes->getNamedItem('href');
                $rel = $node->attributes->getNamedItem('rel');

                if ($node->hasAttributes() &&
                    null !== $href &&
                    null !== $rel) {
                    if ($rel->value == "stylesheet") {
                        $node->parentNode->removeChild($node);
                    }
                }
            }

            $styleTag = $doc->createElement('style', $style);
            $styleTag->setAttribute('type', 'text/css');

            $headTag->appendChild($styleTag);
        }

        return $doc->saveHTML();
    }
}
