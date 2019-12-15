<?php
declare(strict_types=1);
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
 * @file AjaxTagTreeController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Widgets\TagTreeWidget;

/**
 * {@inheritdoc}
 */
class AjaxTagTreeController extends AbstractAjaxController
{
    /**
     * @param Request $request
     * @param null $translationId
     * @return JsonResponse
     */
    public function getTreeAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        /** @var TagTreeWidget|null $tagTree */
        $tagTree = null;

        switch ($request->get("_action")) {
            /*
             * Inner tag edit for tagTree
             */
            case 'requestTagTree':
                if ($request->get('parentTagId') > 0) {
                    $tag = $this->get('em')
                                ->find(
                                    Tag::class,
                                    (int) $request->get('parentTagId')
                                );
                } elseif (null !== $this->getUser() && $this->getUser() instanceof User) {
                    $tag = $this->getUser()->getChroot();
                } else {
                    $tag = null;
                }

                $tagTree = new TagTreeWidget(
                    $this->getRequest(),
                    $this,
                    $tag
                );

                $this->assignation['mainTagTree'] = false;

                break;
            /*
             * Main panel tree tagTree
             */
            case 'requestMainTagTree':
                $parent = null;
                if (null !== $this->getUser() && $this->getUser() instanceof User) {
                    $parent = $this->getUser()->getChroot();
                }

                $tagTree = new TagTreeWidget(
                    $this->getRequest(),
                    $this,
                    $parent
                );
                $this->assignation['mainTagTree'] = true;
                break;
        }

        $this->assignation['tagTree'] = $tagTree;

        $responseArray = [
            'statusCode' => '200',
            'status' => 'success',
            'tagTree' => $this->getTwig()->render('widgets/tagTree/tagTree.html.twig', $this->assignation),
        ];

        return new JsonResponse(
            $responseArray
        );
    }
}
