<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AjaxTagsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Handlers\TagHandler;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

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
                array('content-type' => 'application/javascript')
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
                $responseArray = array(
                    'statusCode' => '200',
                    'status' => 'success',
                    'responseText' => ('Tag '.$tagId.' edited ')
                );
            }

            return new Response(
                json_encode($responseArray),
                Response::HTTP_OK,
                array('content-type' => 'application/javascript')
            );
        }


        $responseArray = array(
            'statusCode' => '403',
            'status'    => 'danger',
            'responseText' => 'Tag '.$tagId.' does not exists'
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
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
                array('content-type' => 'application/javascript')
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $responseArray = array(
            'statusCode' => Response::HTTP_NOT_FOUND,
            'status'    => 'danger',
            'responseText' => 'No tags found'
        );

        if (!empty($request->get('search'))) {

            $responseArray = array();

            $pattern = strip_tags($request->get('search'));
            $tags = $this->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                        ->findBy(
                            array('translatedTag.name' => array('LIKE', '%'.$pattern.'%')),
                            null,
                            10
                        );

            foreach ($tags as $tag) {
                $responseArray[] = $tag->getHandler()->getFullPath();
            }
        }

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
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
        } elseif ($parameters['newParent'] == null) {
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
    }
}
