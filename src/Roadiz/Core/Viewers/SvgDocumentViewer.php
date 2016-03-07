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
 * @file SvgDocumentViewer.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Viewers;

class SvgDocumentViewer
{
    protected $imagePath;
    protected $attributes;
    protected $xml;
    protected $asObject = false;
    protected $imageUrl;

    protected static $allowedAttributes = [
        'width',
        'height',
        'identifier',
        'class',
    ];

    /**
     * @param string  $imagePath
     * @param array   $attributes
     * @param boolean $asObject Default false
     * @param string  $imageUrl Only needed if you set $asObject to true.
     */
    public function __construct(
        $imagePath,
        array $attributes = [],
        $asObject = false,
        $imageUrl = ""
    ) {
        $this->imagePath = $imagePath;
        $this->imageUrl = $imageUrl;
        $this->attributes = $attributes;
        $this->asObject = $asObject;

        if (!file_exists($this->imagePath)) {
            throw new \RuntimeException('SVG file does not exist: ' . $this->imagePath);
        }

        if (false === $this->asObject) {
            $this->xml = new \SimpleXMLElement(file_get_contents($this->imagePath));
            $this->xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
            $this->xml->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');
            $this->xml->registerXPathNamespace('a', 'http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/');
            $this->xml->registerXPathNamespace('ns1', 'http://ns.adobe.com/Flows/1.0/');
            $this->xml->registerXPathNamespace('ns0', 'http://ns.adobe.com/SaveForWeb/1.0/');
            $this->xml->registerXPathNamespace('ns', 'http://ns.adobe.com/Variables/1.0/');
            $this->xml->registerXPathNamespace('i', 'http://ns.adobe.com/AdobeIllustrator/10.0/');
            $this->xml->registerXPathNamespace('x', 'http://ns.adobe.com/Extensibility/1.0/');
            $this->xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $this->xml->registerXPathNamespace('cc', 'http://creativecommons.org/ns#');
            $this->xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
            $this->xml->registerXPathNamespace('sodipodi', 'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd');
            $this->xml->registerXPathNamespace('inkscape', 'http://www.inkscape.org/namespaces/inkscape');
        }
    }

    /**
     * Get SVG string to be used inside HTML content.
     *
     * @return string
     */
    public function getContent()
    {
        if (false === $this->asObject) {
            return $this->getInlineSvg();
        } else {
            return $this->getObjectSvg();
        }
    }

    protected function getInlineSvg()
    {
        $xmlAttributes = $this->xml->attributes();
        $existingAttributesKeys = [];
        foreach ($xmlAttributes as $key => $value) {
            $existingAttributesKeys[] = $key;
        }

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, static::$allowedAttributes)) {
                if ($key == 'identifier') {
                    if (in_array('id', $existingAttributesKeys)) {
                        $xmlAttributes['id'] = $value;
                    } else {
                        $this->xml->addAttribute('id', $value);
                    }
                } else {
                    if (in_array($key, $existingAttributesKeys)) {
                        $xmlAttributes[$key] = $value;
                    } else {
                        $this->xml->addAttribute($key, $value);
                    }
                }
            }
        }

        /*
         * Remove xml DOCTYPE to comply to W3C HTML Validator
         */
        return preg_replace('#^<\?xml[^\?]+\?>#', '', $this->xml->asXML());
    }

    protected function getObjectSvg()
    {
        $attributes = [];
        $attributes['type'] = 'image/svg+xml';
        $attributes['data'] = $this->imageUrl;

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, static::$allowedAttributes)) {
                if ($key == 'identifier') {
                    $attributes['id'] = $value;
                } else {
                    $attributes[$key] = $value;
                }
            }
        }

        if (isset($attributes['alt'])) {
            unset($attributes['alt']);
        }

        $attrs = [];
        foreach ($attributes as $key => $value) {
            $attrs[] = $key . '="' . htmlspecialchars($value) . '"';
        }

        return '<object ' . implode(' ', $attrs) . '></object>';
    }
}
