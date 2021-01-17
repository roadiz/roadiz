<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Widgets\TagTreeWidget;

/**
 * @package Themes\Rozier\AjaxControllers
 */
class AjaxTagTreeController extends AbstractAjaxController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
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
                } else {
                    $tag = null;
                }

                $tagTree = new TagTreeWidget(
                    $this->getRequest(),
                    $this->get('em'),
                    $tag
                );

                $this->assignation['mainTagTree'] = false;

                break;
            /*
             * Main panel tree tagTree
             */
            case 'requestMainTagTree':
                $parent = null;
                $tagTree = new TagTreeWidget(
                    $this->getRequest(),
                    $this->get('em'),
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
