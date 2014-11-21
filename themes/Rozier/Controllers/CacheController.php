<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file CacheController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Console\SchemaCommand;
use RZ\Roadiz\Console\CacheCommand;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class CacheController extends RozierApp
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteDoctrineCache(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCTRINE_CACHE_DELETE');

        $form = $this->buildDeleteDoctrineForm();
        $form->handleRequest();

        if ($form->isValid()) {

            CacheCommand::clearDoctrine();
            CacheCommand::clearRouteCollections();
            CacheCommand::clearTemplates();

            $msg = $this->getTranslator()->trans('cache.deleted');
            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getService('logger')->info($msg);

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('adminHomePage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('cache/deleteDoctrine.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    private function buildDeleteDoctrineForm()
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form');

        return $builder->getForm();
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteSLIRCache(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCTRINE_CACHE_DELETE');

        $form = $this->buildDeleteSLIRForm();
        $form->handleRequest();

        if ($form->isValid()) {

            CacheCommand::clearCachedAssets();

            $msg = $this->getTranslator()->trans('cache.deleted');
            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getService('logger')->info($msg);

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            $response = new RedirectResponse(
                $this->getService('urlGenerator')->generate('adminHomePage')
            );
            $response->prepare($request);

            return $response->send();
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('cache/deleteSLIR.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    private function buildDeleteSLIRForm()
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form');

        return $builder->getForm();
    }
}
