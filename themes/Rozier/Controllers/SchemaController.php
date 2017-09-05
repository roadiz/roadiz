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
 * Description
 *
 * @file SchemaController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use Doctrine\ORM\Mapping\MappingException;
use RZ\Roadiz\Console\RoadizApplication;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\ClearerInterface;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;

/**
 * Redirection controller use to update database schema.
 * THIS CONTROLLER MUST NOT PREPARE ANY DATA.
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
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateNodeTypesSchemaAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');
        $this->clearMetadata();
        $this->updateSchema($request);

        return $this->redirect($this->generateUrl(
            'nodeTypesHomePage'
        ));
    }

    /**
     * @param Request $request
     * @param int $nodeTypeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateNodeTypeFieldsSchemaAction(Request $request, $nodeTypeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODETYPES');
        $this->clearMetadata();
        $this->updateSchema($request);

        return $this->redirect($this->generateUrl(
            'nodeTypeFieldsListPage',
            [
                'nodeTypeId' => $nodeTypeId,
            ]
        ));
    }

    /**
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateThemeSchemaAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        try {
            $this->clearMetadata();
            $this->updateSchema($request);
            return new JsonResponse(['status' => true]);
        } catch (MappingException $e) {
            return new JsonResponse([
                'status' => false,
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function clearThemeCacheAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_THEMES');

        $application = new RoadizApplication(new Kernel('prod', false));
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'cache:clear'
        ));
        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        $inputFpm = new ArrayInput(array(
            'command' => 'cache:clear-fpm'
        ));
        // You can use NullOutput() if you don't need the output
        $outputFpm = new BufferedOutput();
        $application->run($inputFpm, $outputFpm);

        return new JsonResponse(['status' => true]);
    }

    /**
     *
     */
    protected function clearMetadata()
    {
        $clearers = [
            new DoctrineCacheClearer($this->get('em'), $this->get('kernel')),
            new OPCacheClearer(),
        ];

        /** @var ClearerInterface $clearer */
        foreach ($clearers as $clearer) {
            $clearer->clear();
        }
    }

    /**
     * @param Request $request
     */
    protected function updateSchema(Request $request)
    {
        $application = new RoadizApplication(new Kernel('dev', true));
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'orm:schema-tool:update',
            '--dump-sql' => true,
            '--force' => true,
        ));
        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $content = $output->fetch();

        $msg = $this->getTranslator()->trans('database.schema.updated');
        $this->publishConfirmMessage($request, $msg);

        $this->get('logger')->info('DB schema has been updated.', ['sql' => $content]);
    }
}
