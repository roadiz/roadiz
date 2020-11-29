<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Tag;

use Doctrine\ORM\EntityManager;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\StringHandler;

final class TagFactory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * TagFactory constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string           $name
     * @param Translation|null $translation
     * @param Tag|null         $parent
     * @param int|float        $latestPosition
     *
     * @return Tag
     */
    public function create(string $name, ?Translation $translation = null, ?Tag $parent = null, $latestPosition = 0): Tag
    {
        $name = strip_tags(trim($name));
        $tagName = StringHandler::slugify($name);
        if (empty($tagName)) {
            throw new \RuntimeException('Tag name is empty.');
        }
        if (mb_strlen($tagName) > 250) {
            throw new \InvalidArgumentException(sprintf('Tag name "%s" is too long.', $tagName));
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('em');
        $repository = $entityManager->getRepository(Tag::class);

        if (null !== $tag = $repository->findOneByTagName($tagName)) {
            return $tag;
        }

        if ($translation === null) {
            $translation = $this->get('defaultTranslation');
        }

        if ($latestPosition <= 0) {
            /*
             * Get latest position to add tags after.
             * Warning: need to flush between calls
             */
            $latestPosition = $repository->findLatestPositionInParent($parent);
        }

        $tag = new Tag();
        $tag->setTagName($name);
        $tag->setParent($parent);
        $tag->setPosition(++$latestPosition);
        $tag->setVisible(true);
        $this->get('em')->persist($tag);

        $translatedTag = new TagTranslation($tag, $translation);
        $translatedTag->setName($name);
        $this->get('em')->persist($translatedTag);

        return $tag;
    }
}
