<?php
declare(strict_types=1);

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
     * @param Tag[] $tags
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

    /**
     * @param array $data
     *
     * @return Tag
     */
    protected function makeTagRec(array $data): Tag
    {
        $tag = new Tag();
        $tag->setTagName($data['tag_name']);
        $tag->setVisible($data['visible']);
        $tag->setLocked($data['locked']);

        if (isset($data['color'])) {
            $tag->setColor($data['color']);
        }

        foreach ($data["tag_translation"] as $source) {
            $trans = new Translation();
            $trans->setLocale($source['translation']);
            $trans->setName(Translation::$availableLocales[$source['translation']]);

            $tagSource = new TagTranslation($tag, $trans);
            $tagSource->setName($source["title"]);
            $tagSource->setDescription($source["description"]);
            $tagSource->setTag($tag);

            $tag->getTranslatedTags()->add($tagSource);
        }
        foreach ($data['children'] as $child) {
            $tag->addChild($this->makeTagRec($child));
        }
        return $tag;
    }

    /**
     * Deserializes a Json into readable datas.
     *
     * @param string $string
     *
     * @return Tag[]
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
