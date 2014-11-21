<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesUtilsController.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Serializers\TagJsonSerializer;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class TagsUtilsController extends RozierApp
{

    /**
     * Export a Tag in a Json file (.rzn).
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request, $tagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $existingTag = $this->getService('em')
                              ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);
        $this->getService('em')->refresh($existingTag);
        $tag = TagJsonSerializer::serialize(array($existingTag));

        $response =  new Response(
            $tag,
            Response::HTTP_OK,
            array()
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
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportAllAction(Request $request, $tagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $existingTags = $this->getService('em')
                              ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                              ->findBy(array("parent" => null));
        foreach ($existingTags as $existingTag) {
            $this->getService('em')->refresh($existingTag);
        }
        $tag = TagJsonSerializer::serialize($existingTags);

        $response =  new Response(
            $tag,
            Response::HTTP_OK,
            array()
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
