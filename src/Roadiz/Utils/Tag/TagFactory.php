<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file TagFactory.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Tag;

use Doctrine\ORM\EntityManager;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\TagRepository;
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

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('em');
        /** @var TagRepository $repository */
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
