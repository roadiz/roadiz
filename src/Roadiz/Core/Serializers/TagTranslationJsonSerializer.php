<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file TagTranslationJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\NodeSource;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Serializers\EntitySerializer;
use RZ\Roadiz\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Json Serialization handler for NodeSource.
 */
class TagTranslationJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a NodeSource.
     *
     * @param RZ\Roadiz\Core\Entities\NodeSource $nodeSource
     *
     * @return array
     */
    public static function toArray($tt)
    {
        $data = array();

        $data['translation'] = $tt->getTranslation()->getLocale();
        $data['title'] = $tt->getname();
        $data['description'] = $tt->getDescription();


        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @see NodeSourceJsonSerializer::deserializeWithNodeType
     */
    public static function deserialize($string)
    {
        throw new \RuntimeException(
            "Cannot simply deserialize a NodesSources entity. ".
            "Use 'deserializeWithNodeType' method instead.",
            1
        );
    }
}
