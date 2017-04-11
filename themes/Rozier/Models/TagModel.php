<?php
/**
 * Created by PhpStorm.
 * User: adrien
 * Date: 28/03/2017
 * Time: 19:38
 */

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Tag;

class TagModel
{
    public static $thumbnailArray;
    /**
     * @var Tag
     */
    private $tag;
    /**
     * @var Container
     */
    private $container;

    /**
     * NodeModel constructor.
     * @param Tag $tag
     * @param Container $container
     */
    public function __construct(Tag $tag, Container $container)
    {
        $this->tag = $tag;
        $this->container = $container;
    }

    public function toArray()
    {
        $firstTrans = $this->tag->getTranslatedTags()->first();
        $name = $this->tag->getTagName();

        if ($firstTrans) {
            $name = $firstTrans->getName();
        }

        $parent = null;

//        if ($this->tag->getParent()) {
//            $parent = new TagModel($this->tag->getParent(), $this->container);
//            $parent = $parent->toArray();
//        }

        $result = [
            'id' => $this->tag->getId(),
            'name' => $name,
            'tagName' => $this->tag->getTagName(),
            'color' => $this->tag->getColor(),
            'parent' => $this->getTagParents($this->tag)
        ];

        return $result;
    }

    /**
     * @param Tag $tag
     * @param bool $slash
     * @return string
     */
    private function getTagParents ($tag, $slash = false)
    {
        $result = '';
        $parent = $tag->getParent();

        if ($parent) {
            $superParent = $this->getTagParents($parent, true);
            $firstTrans = $parent->getTranslatedTags()->first();
            $name = $parent->getTagName();

            if ($firstTrans) {
                $name = $firstTrans->getName();
            }

            $result = $superParent . $name;

            if ($slash) {
                $result .= ' / ';
            }
        }

        return $result;
    }
}
