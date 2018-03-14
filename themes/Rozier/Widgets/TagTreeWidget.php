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
 *
 * @file TagTreeWidget.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Widgets;

use RZ\Roadiz\CMS\Controllers\Controller;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prepare a Tag tree according to Tag hierarchy and given options.
 */
class TagTreeWidget extends AbstractWidget
{
    protected $parentTag = null;
    protected $tags = null;
    protected $translation = null;
    protected $canReorder = true;

    /**
     * @param Request    $request
     * @param Controller $refereeController
     * @param Tag        $parent
     */
    public function __construct(
        Request $request,
        Controller $refereeController,
        Tag $parent = null
    ) {
        parent::__construct($request, $refereeController);

        $this->parentTag = $parent;
        $this->translation = $this->getController()->get('em')
            ->getRepository(Translation::class)
            ->findOneBy(['defaultTranslation' => true]);
        $this->getTagTreeAssignationForParent();
    }

    /**
     * Fill twig assignation array with TagTree entities.
     */
    protected function getTagTreeAssignationForParent()
    {
        $ordering = [
            'position' => 'ASC',
        ];
        if (null !== $this->parentTag &&
            $this->parentTag->getChildrenOrder() !== 'order' &&
            $this->parentTag->getChildrenOrder() !== 'position') {
            $ordering = [
                $this->parentTag->getChildrenOrder() => $this->parentTag->getChildrenOrderDirection(),
            ];

            $this->canReorder = false;
        }

        $this->tags = $this->getController()->get('em')
             ->getRepository(Tag::class)
            ->findBy(
                [
                     'parent' => $this->parentTag,
                     'translation' => $this->translation,
                 ],
                $ordering
            );
    }

    /**
     * @param Tag $parent
     *
     * @return ArrayCollection
     */
    public function getChildrenTags(Tag $parent)
    {
        if ($parent !== null) {
            $ordering = [
                'position' => 'ASC',
            ];
            if ($parent->getChildrenOrder() !== 'order' &&
                $parent->getChildrenOrder() !== 'position') {
                $ordering = [
                    $parent->getChildrenOrder() => $parent->getChildrenOrderDirection(),
                ];
            }

            return $this->tags = $this->getController()->get('em')
                        ->getRepository(Tag::class)
                        ->findBy([
                            'parent' => $parent,
                            'translation' => $this->translation,
                        ], $ordering);
        }

        return null;
    }
    /**
     * @return Tag
     */
    public function getRootTag()
    {
        return $this->parentTag;
    }
    /**
     * @return \RZ\Roadiz\Core\Entities\Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }
    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Gets the value of canReorder.
     *
     * @return boolean
     */
    public function getCanReorder()
    {
        return $this->canReorder;
    }
}
