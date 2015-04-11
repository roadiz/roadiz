<?php
/*
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
 * @file AjaxTagsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\FilterTagEvent;
use RZ\Roadiz\Core\Events\TagEvents;
use RZ\Roadiz\Core\Handlers\TagHandler;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;

/**
 * {@inheritdoc}
 */
class AjaxTagsController extends AbstractAjaxController
{
    /**
     * Handle AJAX edition requests for Tag
     * such as comming from tagtree widgets.
     *
     * @param Request $request
     * @param int     $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response JSON response
     */
    public function editAction(Request $request, $tagId)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $tag = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);

        if ($tag !== null) {
            $responseArray = null;

            /*
             * Get the right update method against "_action" parameter
             */
            switch ($request->get('_action')) {
                case 'updatePosition':
                    $responseArray = $this->updatePosition($request->request->all(), $tag);
                    break;
            }

            if ($responseArray === null) {
                $responseArray = [
                    'statusCode' => '200',
                    'status' => 'success',
                    'responseText' => ('Tag ' . $tagId . ' edited '),
                ];
            }

            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
            );
        }

        $responseArray = [
            'statusCode' => '403',
            'status' => 'danger',
            'responseText' => 'Tag ' . $tagId . ' does not exists',
        ];

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            ['content-type' => 'application/javascript']
        );
    }

    public function searchAction(Request $request)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request, 'GET')) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_OK,
                ['content-type' => 'application/javascript']
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $responseArray = [
            'statusCode' => Response::HTTP_NOT_FOUND,
            'status' => 'danger',
            'responseText' => 'No tags found',
        ];

        if ($request->get('search') != "") {
            $responseArray = [];

            $pattern = strip_tags($request->get('search'));

            $tags = $this->getService('em')
                         ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                         ->searchBy($pattern, [], [], 10);

            if (0 === count($tags)) {
                /*
                 * Try again using tag slug
                 */
                $pattern = StringHandler::slugify($pattern);
                $tags = $this->getService('em')
                             ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                             ->searchBy($pattern, [], [], 10);
            }

            foreach ($tags as $tag) {
                $responseArray[] = $tag->getHandler()->getFullPath();
            }
        }

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            ['content-type' => 'application/javascript']
        );
    }

    /**
     * @param array $parameters
     * @param Tag   $tag
     */
    protected function updatePosition($parameters, Tag $tag)
    {
        /*
         * First, we set the new parent
         */
        $parent = null;

        if (!empty($parameters['newParent']) &&
            $parameters['newParent'] > 0) {
            $parent = $this->getService('em')
                           ->find('RZ\Roadiz\Core\Entities\Tag', (int) $parameters['newParent']);

            if ($parent !== null) {
                $tag->setParent($parent);
            }
        } else {
            $tag->setParent(null);
        }

        /*
         * Then compute new position
         */
        if (!empty($parameters['nextTagId']) &&
            $parameters['nextTagId'] > 0) {
            $nextTag = $this->getService('em')
                            ->find('RZ\Roadiz\Core\Entities\Tag', (int) $parameters['nextTagId']);
            if ($nextTag !== null) {
                $tag->setPosition($nextTag->getPosition() - 0.5);
            }
        } elseif (!empty($parameters['prevTagId']) &&
            $parameters['prevTagId'] > 0) {
            $prevTag = $this->getService('em')
                            ->find('RZ\Roadiz\Core\Entities\Tag', (int) $parameters['prevTagId']);
            if ($prevTag !== null) {
                $tag->setPosition($prevTag->getPosition() + 0.5);
            }
        }
        // Apply position update before cleaning
        $this->getService('em')->flush();

        if ($parent !== null) {
            $parent->getHandler()->cleanChildrenPositions();
        } else {
            TagHandler::cleanRootTagsPositions();
        }

        /*
         * Dispatch event
         */
        $event = new FilterTagEvent($tag);
        $this->getService('dispatcher')->dispatch(TagEvents::TAG_UPDATED, $event);
    }
}
