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
 * @file TagJsonSerializer.php
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * Json Serialization handler for Node.
 */
class TagJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a Tag.
     *
     * @param \RZ\Roadiz\Core\Entities\Tag[] $tags
     *
     * @return array
     */
    public function toArray($tags)
    {
        $ttSerializer = new TagTranslationJsonSerializer();
        $array = [];

        foreach ($tags as $tag) {
            $data = [];

            $data['tag_name'] = $tag->getTagName();
            $data['visible'] = $tag->isVisible();
            $data['locked'] = $tag->isLocked();
            $data['color'] = $tag->getColor();

            $data['children'] = [];
            $data['tag_translation'] = [];

            foreach ($tag->getTranslatedTags() as $source) {
                $data['tag_translation'][] = $ttSerializer->toArray($source);
            }
            /*
             * Recursivity !! Be careful
             */
            foreach ($tag->getChildren() as $child) {
                $data['children'][] = $this->toArray([$child])[0];
            }
            $array[] = $data;
        }
        return $array;
    }

    protected function makeTagRec($data)
    {
        $tag = new Tag();
        $tag->setTagName($data['tag_name']);
        $tag->setVisible($data['visible']);
        $tag->setLocked($data['locked']);
        $tag->setColor($data['color']);

        foreach ($data["tag_translation"] as $source) {
            $trans = new Translation();
            $trans->setLocale($source['translation']);
            $trans->setName(Translation::$availableLocales[$source['translation']]);

            $tagSource = new TagTranslation($tag, $trans);
            $tagSource->setName($source["title"]);
            $tagSource->setDescription($source["description"]);

            $tag->getTranslatedTags()->add($tagSource);
        }
        foreach ($data['children'] as $child) {
            $tmp = $this->makeTagRec($child);
            $tag->addChild($tmp);
        }
        return $tag;
    }

    /**
     * Deserializes a Json into readable datas.
     *
     * @param string $string
     *
     * @return \RZ\Roadiz\Core\Entities\Node
     */
    public function deserialize($string)
    {
        $datas = json_decode($string, true);
        $array = [];
        foreach ($datas as $data) {
            $array[] = $this->makeTagRec($data);
        }
        return $array;
    }
}
