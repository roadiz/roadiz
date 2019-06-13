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
 * @file NodesUtilsController.php
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers\Tags;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Serializers\TagJsonSerializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class TagsUtilsController extends RozierApp
{

    /**
     * Export a Tag in a Json file (.rzn).
     *
     * @param Request $request
     * @param int     $tagId
     *
     * @return Response
     */
    public function exportAction(Request $request, $tagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $existingTag = $this->get('em')
                              ->find(Tag::class, (int) $tagId);
        $this->get('em')->refresh($existingTag);

        $serializer = new TagJsonSerializer();
        $tag = $serializer->serialize([$existingTag]);

        $response =  new Response(
            $tag,
            Response::HTTP_OK,
            []
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'tag-' . $existingTag->getTagName() . '-' . date("YmdHis")  . '.rzg'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

    /**
     * Export a Tag in a Json file (.rzn).
     *
     * @param Request $request
     * @param int     $tagId
     *
     * @return Response
     */
    public function exportAllAction(Request $request, $tagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $existingTags = $this->get('em')
                              ->getRepository(Tag::class)
                              ->findBy(["parent" => null]);
        foreach ($existingTags as $existingTag) {
            $this->get('em')->refresh($existingTag);
        }
        $serializer = new TagJsonSerializer();
        $tag = $serializer->serialize($existingTags);

        $response =  new Response(
            $tag,
            Response::HTTP_OK,
            []
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'tag-all-' . date("YmdHis")  . '.rzg'
            )
        ); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }
}
