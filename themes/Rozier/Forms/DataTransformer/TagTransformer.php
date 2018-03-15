<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file NodeTypeTransformer.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class TagTransformer
 * @package Themes\Rozier\Forms\DataTransformer
 */
class TagTransformer implements DataTransformerInterface
{
    private $manager;

    /**
     * NodeTypeTransformer constructor.
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param ArrayCollection $tags
     * @return array|string
     */
    public function transform($tags)
    {
        if (null === $tags || empty($tags)) {
            return '';
        }
        $ids = [];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $ids[] = $tag->getId();
        }
        return $ids;
    }

    /**
     * @param string $tagIds
     * @return array
     */
    public function reverseTransform($tagIds)
    {
        if (!$tagIds) {
            return [];
        }

        if (is_array($tagIds)) {
            $ids = $tagIds;
        } else {
            $ids = explode(',', $tagIds);
        }

        $tags = [];
        foreach ($ids as $tagId) {
            $tag = $this->manager
                ->getRepository(Tag::class)
                ->find($tagId)
            ;
            if (null === $tag) {
                throw new TransformationFailedException(sprintf(
                    'A tag with id "%s" does not exist!',
                    $tagId
                ));
            }

            $tags[] = $tag;
        }

        return $tags;
    }
}
