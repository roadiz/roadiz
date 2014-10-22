<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file TagJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\TagTranslation;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Serializers\TagTranslationJsonSerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Json Serialization handler for Node.
 */
class TagJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a Tag.
     *
     * @param RZ\Renzo\Core\Entities\Tag $tag
     *
     * @return array
     */
    public static function toArray($tags)
    {
        $array = array();

        foreach ($tags as $tag) {
            $data = array();

            $data['tag_name'] = $tag->getTagName();
            $data['visible'] =  $tag->isVisible();
            $data['locked'] =   $tag->isLocked();

            $data['children'] =  array();
            $data['tag_translation'] = array();

            foreach ($tag->getTranslatedTags() as $source) {
                $data['tag_translation'][] = TagTranslationJsonSerializer::toArray($source);
            }
            /*
             * Recursivity !! Be careful
             */
            foreach ($tag->getChildren() as $child) {
                $data['children'][] = static::toArray(array($child))[0];
            }
            $array[] = $data;
        }
        return $array;
    }

    private static function makeTagRec($data) {
        $tag = new Tag();
        $tag->setTagName($data['tag_name']);
        $tag->setVisible($data['visible']);
        $tag->setLocked($data['locked']);

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
            $tmp = static::makeTagRec($child);
            $tag->addChild($tmp);
        }
        return $tag;
    }

    /**
     * Deserializes a Json into readable datas.
     *
     * @param string $string
     *
     * @return RZ\Renzo\Core\Entities\Node
     */
    public static function deserialize($string)
    {
        $datas = json_decode($string, true);
        $array = array();
        foreach ($datas as $data) {
            $array[] = static::makeTagRec($data);
        }
        return $array;
    }
}
