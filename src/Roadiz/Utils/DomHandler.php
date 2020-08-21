<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils;

use DOMNode;

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
        /** @var DOMNode $node */
        foreach ($elements as $node) {
            $href = $node->attributes->getNamedItem('href');
            $rel = $node->attributes->getNamedItem('rel');

            if ($node->hasAttributes() &&
                null !== $href &&
                null !== $rel) {
                if (isset($rel->value) &&
                    isset($href->value) &&
                    $rel->value == "stylesheet") {
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
     * @return string
     */
    public static function getExternalStyles($dom)
    {
        $cssFiles = static::getExternalStylesFiles($dom);

        // Concat all css-file in one string
        $cssContent = "";
        foreach ($cssFiles as $css) {
            if (file_exists($css)) {
                $cssContent .= file_get_contents($css) . PHP_EOL;
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
                    if (isset($rel->value) && $rel->value == "stylesheet") {
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
