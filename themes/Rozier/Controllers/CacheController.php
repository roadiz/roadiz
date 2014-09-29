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

use RZ\Renzo\Console\SchemaCommand;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\TagTranslation;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
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
        $this->validedAccessForRole('ROLE_ACCESS_DOCTRINE_CACHE_DELETE');
        // if (!($this->getSecurityContext()->isGranted('ROLE_ACCESS_DOCTRINE_CACHE_DELETE')
        //     || $this->getSecurityContext()->isGranted('ROLE_SUPERADMIN')))
        //     return $this->throw404();

        $form = $this->buildDeleteDoctrineForm();
        $form->handleRequest();

        if ($form->isValid()) {

            SchemaCommand::refreshMetadata();
            $msg = $this->getTranslator()->trans('cache.deleted');
            $request->getSession()->getFlashBag()->add('confirm', $msg);
            $this->getLogger()->info($msg);

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
        $builder = $this->getFormFactory()
            ->createBuilder('form');

        return $builder->getForm();
    }
}
