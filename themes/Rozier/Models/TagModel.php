<?php
declare(strict_types=1);

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class TagModel.
 *
 * @package Themes\Rozier\Models
 */
class TagModel implements ModelInterface
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

        /** @var UrlGenerator $urlGenerator */
        $urlGenerator = $this->container->offsetGet('urlGenerator');

        $result = [
            'id' => $this->tag->getId(),
            'name' => $name,
            'tagName' => $this->tag->getTagName(),
            'color' => $this->tag->getColor(),
            'parent' => $this->getTagParents($this->tag),
            'editUrl' => $urlGenerator->generate('tagsEditPage', [
                'tagId' => $this->tag->getId()
            ]),
        ];

        return $result;
    }

    /**
     * @param Tag $tag
     * @param bool $slash
     * @return string
     */
    private function getTagParents($tag, $slash = false)
    {
        $result = '';
        $parent = $tag->getParent();

        if (null !== $parent && $parent instanceof Tag) {
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
