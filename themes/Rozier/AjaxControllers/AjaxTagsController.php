<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\Tag\TagUpdatedEvent;
use RZ\Roadiz\Core\Handlers\TagHandler;
use RZ\Roadiz\Core\Repositories\TagRepository;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Themes\Rozier\Models\TagModel;

/**
 * @package Themes\Rozier\AjaxControllers
 */
class AjaxTagsController extends AbstractAjaxController
{
    /**
     * @return TagRepository
     */
    protected function getRepository()
    {
        return $this->get('em')->getRepository(Tag::class);
    }

    /**
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');
        $onlyParents = false;

        if ($request->query->has('onlyParents') &&
            $request->query->get('onlyParents') == true) {
            $onlyParents = true;
        }

        if ($onlyParents) {
            $tags = $this->getRepository()->findByParentWithChildrenAndDefaultTranslation();
        } else {
            $tags = $this->getRepository()->findByParentWithDefaultTranslation();
        }

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'tags' => $this->recurseTags($tags, $onlyParents),
        ];

        return new JsonResponse(
            $responseArray
        );
    }

    /**
     * Get a Tag list from an array of node id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listArrayAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        if (!$request->query->has('ids') || !is_array($request->query->get('ids'))) {
            throw new InvalidParameterException('Ids should be provided within an array');
        }

        $cleanTagIds = array_filter($request->query->get('ids'));
        $tags = $this->getRepository()->findBy([
            'id' => $cleanTagIds,
        ]);

        // Sort array by ids given in request
        $tags = $this->sortIsh($tags, $cleanTagIds);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'tags' => $this->normalizeTags($tags)
        ];

        return new JsonResponse(
            $responseArray
        );
    }

    /**
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function explorerListAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $arrayFilter = [
            'translation' => $this->get('defaultTranslation')
        ];
        $defaultOrder = [
            'createdAt' => 'DESC'
        ];

        if ($request->get('tagId') > 0) {
            $parentTag = $this->get('em')
                ->find(
                    Tag::class,
                    $request->get('tagId')
                );

            $arrayFilter['parent'] = $parentTag;
        }

        if ($request->query->has('onlyParents')) {
            $arrayFilter['children'] = ['NOT NULL'];
        }

        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            Tag::class,
            $arrayFilter,
            $defaultOrder
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setItemPerPage(30);
        $listManager->handle();

        $tags = $listManager->getEntities();

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'tags' => $this->normalizeTags($tags),
            'filters' => $listManager->getAssignation(),
        ];

        return new JsonResponse(
            $responseArray
        );
    }

    /**
     * @param Tag[]|null $tags
     * @return array
     */
    protected function normalizeTags(array $tags = null)
    {
        $tagsArray = [];
        if ($tags !== null) {
            /** @var Tag $tag */
            foreach ($tags as $tag) {
                $tagModel = new TagModel($tag, $this->container);
                $tagsArray[] = $tagModel->toArray();
            }
        }

        return $tagsArray;
    }

    /**
     * @param Tag[]|null $tags
     * @param bool $onlyParents
     *
     * @return array
     */
    protected function recurseTags(array $tags = null, bool $onlyParents = false)
    {
        $tagsArray = [];
        if ($tags !== null) {
            foreach ($tags as $tag) {
                if ($onlyParents) {
                    $children = $this->getRepository()->findByParentWithChildrenAndDefaultTranslation($tag);
                } else {
                    $children = $this->getRepository()->findByParentWithDefaultTranslation($tag);
                }

                $tagsArray[] = [
                    'id' => $tag->getId(),
                    'name' => $tag->getTranslatedTags()->first() ? $tag->getTranslatedTags()->first()->getName() : $tag->getTagName(),
                    'children' => $this->recurseTags($children, $onlyParents),
                ];
            }
        }

        return $tagsArray;
    }

    /**
     * Handle AJAX edition requests for Tag
     * such as coming from tag-tree widgets.
     *
     * @param Request $request
     * @param int     $tagId
     *
     * @return Response JSON response
     */
    public function editAction(Request $request, int $tagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $tag = $this->get('em')->find(Tag::class, (int) $tagId);

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

            return new JsonResponse(
                $responseArray,
                Response::HTTP_PARTIAL_CONTENT
            );
        }

        throw $this->createNotFoundException('Tag ' . $tagId . ' does not exists');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        if ($request->get('search') != "") {
            $responseArray = [];

            $pattern = strip_tags($request->get('search'));

            $tags = $this->getRepository()
                         ->searchBy($pattern, [], [], 10);

            if (0 === count($tags)) {
                /*
                 * Try again using tag slug
                 */
                $pattern = StringHandler::slugify($pattern);
                $tags = $this->getRepository()
                             ->searchBy($pattern, [], [], 10);
            }

            if (count($tags) > 0) {
                /** @var Tag $tag */
                foreach ($tags as $tag) {
                    $responseArray[] = $tag->getFullPath();
                }

                return new JsonResponse(
                    $responseArray
                );
            } else {
                throw $this->createNotFoundException('No tags found.');
            }
        }

        throw new BadRequestHttpException('Search is empty.');
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
            $parent = $this->get('em')
                           ->find(Tag::class, (int) $parameters['newParent']);

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
            $nextTag = $this->get('em')
                            ->find(Tag::class, (int) $parameters['nextTagId']);
            if ($nextTag !== null) {
                $tag->setPosition($nextTag->getPosition() - 0.5);
            }
        } elseif (!empty($parameters['prevTagId']) &&
            $parameters['prevTagId'] > 0) {
            $prevTag = $this->get('em')
                            ->find(Tag::class, (int) $parameters['prevTagId']);
            if ($prevTag !== null) {
                $tag->setPosition($prevTag->getPosition() + 0.5);
            }
        }
        // Apply position update before cleaning
        $this->get('em')->flush();

        /** @var TagHandler $tagHandler */
        $tagHandler = $this->get('tag.handler');
        $tagHandler->setTag($tag);
        $tagHandler->cleanPositions();

        $this->get('em')->flush();

        /*
         * Dispatch event
         */
        $this->get('dispatcher')->dispatch(new TagUpdatedEvent($tag));
    }

    /**
     * Create a new Tag.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        if (!$request->get('tagName')) {
            throw new InvalidParameterException('tagName should be provided to create a new Tag');
        }

        if ($request->getMethod() != Request::METHOD_POST) {
            throw new BadRequestHttpException();
        }

        /** @var Tag $tag */
        $tag = $this->getRepository()->findOrCreateByPath($request->get('tagName'));
        $tagModel = new TagModel($tag, $this->getContainer());

        return new JsonResponse(
            [
                'tag' => $tagModel->toArray()
            ],
            Response::HTTP_CREATED
        );
    }
}
