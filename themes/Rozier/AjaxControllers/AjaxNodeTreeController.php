<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file AjaxNodeTreeController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Themes\Rozier\AjaxControllers\AbstractAjaxController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Themes\Rozier\Widgets\NodeTreeWidget;

/**
 * {@inheritdoc}
 */
class AjaxNodeTreeController extends AbstractAjaxController
{
    public function getTreeAction(Request $request, $translationId = null)
    {
        /*
         * Validate
         */
        if (true !== $notValid = $this->validateRequest($request)) {
            return new Response(
                json_encode($notValid),
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/javascript')
            );
        }

        $this->validateAccessForRole('ROLE_ACCESS_NODES');


        $translation = null;

        if (null === $translationId) {
            $translation = $this->getService('em')
                                ->getRepository('\RZ\Roadiz\Core\Entities\Translation')
                                ->findDefault();
        } else {
            $translation = $this->getService('em')
                                ->find(
                                    '\RZ\Roadiz\Core\Entities\Translation',
                                    (int) $translationId
                                );
        }


        switch ($request->get("_action")) {
            /*
             * Inner node edit for nodeTree
             */
            case 'requestNodeTree':
                if ($request->get('parentNodeId') > 0) {

                    $node = $this->getService('em')
                                 ->find(
                                     '\RZ\Roadiz\Core\Entities\Node',
                                     (int) $request->get('parentNodeId')
                                 );

                    $this->assignation['nodeTree'] = new NodeTreeWidget(
                        $this->getKernel()->getRequest(),
                        $this,
                        $node,
                        $translation
                    );
                    $this->assignation['mainNodeTree'] = false;

                    if (true == $request->get('stackTree')) {
                        $this->assignation['nodeTree']->setStackTree(true);
                    }

                } else {
                    throw new \RuntimeException("No root node specified", 1);
                }

                break;
            /*
             * Main panel tree nodeTree
             */
            case 'requestMainNodeTree':
                $this->assignation['nodeTree'] = new NodeTreeWidget(
                    $this->getKernel()->getRequest(),
                    $this,
                    null,
                    $translation
                );
                $this->assignation['mainNodeTree'] = true;

                break;
        }


        $responseArray = array(
            'statusCode' => '200',
            'status' => 'success',
            'nodeTree' => $this->getTwig()->render('widgets/nodeTree/nodeTree.html.twig', $this->assignation),
        );

        return new Response(
            json_encode($responseArray),
            Response::HTTP_OK,
            array('content-type' => 'application/javascript')
        );
    }
}
