<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file SchemaController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\CMS\Controllers\FrontendController;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Redirection controller use to update database schema.
 */
class SchemaController extends RozierApp
{
    /**
     * No preparation for this blind controller.
     *
     * @return $this
     */
    public function prepareBaseAssignation()
    {
        return $this;
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $_token
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function updateNodeTypesSchemaAction(Request $request, $_token)
    {
        $this->validedAccessForRole('ROLE_ACCESS_NODETYPES');
        // if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_NODETYPES')
        //     || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
        //     return $this->throw404();

        if ($this->getKernel()
                ->getCsrfProvider()
                ->isCsrfTokenValid(static::SCHEMA_TOKEN_INTENTION, $_token)) {

            \RZ\Renzo\Console\SchemaCommand::updateSchema();

            $msg = $this->getTranslator()->trans('database.schema.updated');
            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getLogger()->info($msg);
        } else {
            $msg = $this->getTranslator()->trans('database.schema.cannot_updated');
            $request->getSession()->getFlashBag()->add('error', $msg);
            $this->getLogger()->error($msg);
        }
        /*
         * Redirect to update schema page
         */
        $response = new RedirectResponse(
            $this->getService('urlGenerator')->generate(
                'nodeTypesHomePage'
            )
        );
        $response->prepare($request);

        return $response->send();
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $_token
     * @param int                                      $nodeTypeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function updateNodeTypeFieldsSchemaAction(Request $request, $_token, $nodeTypeId)
    {
        $this->validedAccessForRole('ROLE_ACCESS_NODETYPES');
        // if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_NODETYPES')
        //     || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
        //     return $this->throw404();

        if ($this->getKernel()
                ->getCsrfProvider()
                ->isCsrfTokenValid(static::SCHEMA_TOKEN_INTENTION, $_token)) {
            \RZ\Renzo\Console\SchemaCommand::updateSchema();

            $msg = $this->getTranslator()->trans('database.schema.updated');
            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getLogger()->info($msg);
        } else {
            $msg = $this->getTranslator()->trans('database.schema.cannot_updated');
            $request->getSession()->getFlashBag()->add('error', $msg);
            $this->getLogger()->error($msg);
        }
        /*
         * Redirect to update schema page
         */
        $response = new RedirectResponse(
            $this->getService('urlGenerator')->generate(
                'nodeTypeFieldsListPage',
                array(
                    'nodeTypeId' => $nodeTypeId
                )
            )
        );
        $response->prepare($request);

        return $response->send();
    }
}
