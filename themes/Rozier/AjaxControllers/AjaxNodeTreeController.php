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

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeHandler;
use Themes\Rozier\AjaxControllers\AbstractAjaxController;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\Widgets\TagTreeWidget;

/**
 * {@inheritdoc}
 */
class AjaxNodeTreeController extends AbstractAjaxController
{
    public function getTreeAction(Request $request)
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


        switch ($request->get("_action")) {
            /*
             * Inner node edit for nodeTree
             */
            case 'requestNodeTree':
                if ($request->get('parentNodeId') > 0) {

                    $node = $this->getService('em')
                                 ->find(
                                    '\RZ\Renzo\Core\Entities\Node',
                                    (int) $request->get('parentNodeId')
                                 );

                    $this->assignation['nodeTree'] = new NodeTreeWidget(
                        $this->getKernel()->getRequest(),
                        $this,
                        $node
                    );
                    $this->assignation['mainNodeTree'] = false;

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
                    $this
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
