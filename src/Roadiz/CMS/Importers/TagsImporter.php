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
 * @file TagsImporter.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Importers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Serializers\TagJsonSerializer;

use RZ\Roadiz\CMS\Importers\ImporterInterface;

/**
 * {@inheritdoc}
 */
class TagsImporter implements ImporterInterface
{
    /**
     * Import a Json file (.rzt) containing tag and tag translation.
     *
     * @param string $serializedData
     *
     * @return bool
     */
    public static function importJsonFile($serializedData)
    {
        $tags = TagJsonSerializer::deserialize($serializedData);
        $exist = Kernel::getInstance()->getService('em')
                      ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                      ->findAll();
        if (empty($exist)) {
            foreach ($tags as $tag) {
                static::browseTree($tag);
            }
        }

        return true;
    }

    protected static function browseTree($tag)
    {
        $childObj = [];
        $sourceObj = [];
        foreach ($tag->getChildren() as $child) {
            $childObj[] = static::browseTree($child);
        }
        $tag->getChildren()->clear();
        foreach ($tag->getTranslatedTags() as $tagTranslation) {
            $trans = Kernel::getInstance()->getService('em')
                          ->getRepository("RZ\Roadiz\Core\Entities\Translation")
                          ->findOneByLocale($tagTranslation->getTranslation()->getLocale());

            if (empty($trans)) {
                $trans = new Translation();
                $trans->setLocale($tagTranslation->getTranslation()->getLocale());
                $trans->setName(Translation::$availableLocales[$tagTranslation->getTranslation()->getLocale()]);
                Kernel::getInstance()->getService('em')->persist($trans);
            }
            $tagTranslation->setTranslation($trans);
            $tagTranslation->setTag(null);
            Kernel::getInstance()->getService('em')->persist($tagTranslation);
            $sourceObj[] = $tagTranslation;
        }
        Kernel::getInstance()->getService('em')->persist($tag);
        foreach ($childObj as $child) {
            $child->setParent($tag);
        }
        foreach ($sourceObj as $tagTranslation) {
            $tagTranslation->setTag($tag);
        }
        Kernel::getInstance()->getService('em')->flush();

        return $tag;
    }
}
