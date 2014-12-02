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

use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        if ($this->getService('csrfProvider')
                ->isCsrfTokenValid(static::SCHEMA_TOKEN_INTENTION, $_token)) {

            \RZ\Roadiz\Console\SchemaCommand::updateSchema();

            $msg = $this->getTranslator()->trans('database.schema.updated');
            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getService('logger')->info($msg);
        } else {
            $msg = $this->getTranslator()->trans('database.schema.cannot_updated');
            $request->getSession()->getFlashBag()->add('error', $msg);
            $this->getService('logger')->error($msg);
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
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');

        if ($this->getService('csrfProvider')
                ->isCsrfTokenValid(static::SCHEMA_TOKEN_INTENTION, $_token)) {
            \RZ\Roadiz\Console\SchemaCommand::updateSchema();

            $msg = $this->getTranslator()->trans('database.schema.updated');
            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getService('logger')->info($msg);
        } else {
            $msg = $this->getTranslator()->trans('database.schema.cannot_updated');
            $request->getSession()->getFlashBag()->add('error', $msg);
            $this->getService('logger')->error($msg);
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
