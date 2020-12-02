<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use Doctrine\ORM\Mapping\MappingException;
use RZ\Roadiz\Console\RoadizApplication;
use RZ\Roadiz\Utils\Clearer\ClearerInterface;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use Symfony\Component\Console\Application;
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_THEMES');

        try {
            $this->clearMetadata();
            $this->updateSchema($request);
            return new JsonResponse(['status' => true], JsonResponse::HTTP_PARTIAL_CONTENT);
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
        $this->denyAccessUnlessGranted('ROLE_ACCESS_THEMES');

        /*
         * Very important, when using standard-edition,
         * Kernel class is AppKernel or DevAppKernel.
         */
        $kernelClass = get_class($this->get('kernel'));
        $application = new RoadizApplication(new $kernelClass('prod', false));
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'cache:clear'
        ]);
        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        $inputFpm = new ArrayInput([
            'command' => 'cache:clear-fpm'
        ]);
        // You can use NullOutput() if you don't need the output
        $outputFpm = new BufferedOutput();
        $application->run($inputFpm, $outputFpm);

        return new JsonResponse(['status' => true], JsonResponse::HTTP_PARTIAL_CONTENT);
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

    protected function createApplication(): Application
    {
        /*
         * Very important, when using standard-edition,
         * Kernel class is AppKernel or DevAppKernel.
         */
        $kernelClass = get_class($this->get('kernel'));
        $application = new RoadizApplication(new $kernelClass('dev', true));
        $application->setAutoExit(false);
        return $application;
    }

    /**
     * @param Request $request
     */
    protected function updateSchema(Request $request)
    {
        $input = new ArrayInput([
            'command' => 'migrations:diff',
            '--allow-empty-diff' => true,
            '--no-interaction' => true
        ]);
        $output = new BufferedOutput();
        $this->createApplication()->run($input, $output);
        $content = $output->fetch();
        $this->get('logger.doctrine')->info('New migration generated.', ['migration' => $content]);

        $input = new ArrayInput([
            'command' => 'migrations:migrate',
            '--no-interaction' => true,
            '--allow-no-migration' => true
        ]);
        $output = new BufferedOutput();
        $this->createApplication()->run($input, $output);
        $content = $output->fetch();

        $msg = $this->getTranslator()->trans('database.schema.updated');
        $this->publishConfirmMessage($request, $msg);

        $this->get('logger.doctrine')->info('DB schema has been updated.', ['sql' => $content]);
    }
}
