<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\CustomForms;

use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\CustomFormType;
use Themes\Rozier\RozierApp;

/**
 * Class CustomFormsController
 *
 * @package Themes\Rozier\Controllers
 */
class CustomFormsController extends RozierApp
{
    /**
     * List every node-types.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS');
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            CustomForm::class,
            [],
            ['createdAt' => 'DESC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['custom_forms'] = $listManager->getEntities();

        return $this->render('custom-forms/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for requested node-type.
     *
     * @param Request   $request
     * @param int $customFormId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $customFormId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS');

        /** @var CustomForm $customForm */
        $customForm = $this->get('em')->find(CustomForm::class, (int) $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            $form = $this->createForm(CustomFormType::class, $customForm, [
                'em' => $this->get('em'),
                'name' => $customForm->getName(),
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->get('em')->flush();
                    $msg = $this->getTranslator()->trans('customForm.%name%.updated', ['%name%' => $customForm->getName()]);
                    $this->publishConfirmMessage($request, $msg);
                    return $this->redirect($this->generateUrl('customFormsEditPage', [
                        'customFormId' => $customForm->getId(),
                    ]));
                } catch (EntityAlreadyExistsException $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-forms/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an creation form for requested node-type.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS');
        $customForm = new CustomForm();

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;

            /*
             * form
             */
            $form = $this->createForm(CustomFormType::class, $customForm, [
                'em' => $this->get('em'),
            ]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->get('em')->persist($customForm);
                    $this->get('em')->flush();

                    $msg = $this->getTranslator()->trans('customForm.%name%.created', ['%name%' => $customForm->getName()]);
                    $this->publishConfirmMessage($request, $msg);

                    return $this->redirect($this->generateUrl('customFormsEditPage', [
                        'customFormId' => $customForm->getId(),
                    ]));
                } catch (EntityAlreadyExistsException $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-forms/add.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an deletion form for requested node-type.
     *
     * @param Request $request
     * @param int     $customFormId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $customFormId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        /** @var CustomForm $customForm */
        $customForm = $this->get('em')
                           ->find(CustomForm::class, (int) $customFormId);

        if (null !== $customForm) {
            $this->assignation['customForm'] = $customForm;
            $form = $this->createForm(FormType::class, $customForm);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->get("em")->remove($customForm);
                $this->get("em")->flush();

                $msg = $this->getTranslator()->trans('customForm.%name%.deleted', ['%name%' => $customForm->getName()]);
                $this->publishConfirmMessage($request, $msg);
                /*
                 * Redirect to update schema page
                 */
                return $this->redirect($this->generateUrl(
                    'customFormsHomePage'
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('custom-forms/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }
}
