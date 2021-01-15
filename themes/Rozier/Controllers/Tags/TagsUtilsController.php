<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Tags;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers\Tags
 */
class TagsUtilsController extends RozierApp
{
    /**
     * Export a Tag in a Json file
     *
     * @param Request $request
     * @param int     $tagId
     *
     * @return Response
     */
    public function exportAction(Request $request, int $tagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $existingTag = $this->get('em')->find(Tag::class, $tagId);

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                $existingTag,
                'json',
                SerializationContext::create()->setGroups(['tag', 'position'])
            ),
            JsonResponse::HTTP_OK,
            [
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    'tag-' . $existingTag->getTagName() . '-' . date("YmdHis")  . '.json'
                ),
            ],
            true
        );
    }

    /**
     * Export a Tag in a Json file
     *
     * @param Request $request
     * @param int $tagId
     *
     * @return Response
     */
    public function exportAllAction(Request $request, int $tagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $existingTags = $this->get('em')
                              ->getRepository(Tag::class)
                              ->findBy(["parent" => null]);

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                $existingTags,
                'json',
                SerializationContext::create()->setGroups(['tag', 'position'])
            ),
            JsonResponse::HTTP_OK,
            [
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    'tag-all-' . date("YmdHis") . '.json'
                ),
            ],
            true
        );
    }
}
