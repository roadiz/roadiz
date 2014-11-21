<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
 *
 *
 * @file CustomFormsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Core\Entities\CustomFormFieldAttribute;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
* CustomForm controller
*/
class CustomFormAnswersController extends RozierApp
{
    /**
     * List every node-types.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, $customFormId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\CustomFormAnswer',
            array("customForm" => $customFormId)
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['custom_form_answers'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('custom-form-answers/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an deletion form for requested node-type.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $customFormAnswerId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $customFormAnswer = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\CustomFormAnswer', (int) $customFormAnswerId);

        if (null !== $customFormAnswer) {
            $this->assignation['customFormAnswer'] = $customFormAnswer;

            $form = $this->buildDeleteForm($customFormAnswer);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['customFormAnswerId'] == $customFormAnswer->getId() ) {

                $this->getService("em")->remove($customFormAnswer);

                $msg = $this->getTranslator()->trans('customFormAnswer.%id%.deleted', array('%id%'=>$customFormAnswer->getId()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
                /*
                 * Redirect to update schema page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'customFormAnswersHomePage', array("customFormId" => $customFormAnswer->getCustomForm()->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('custom-form-answers/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\CustomForm $customForm
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(CustomFormAnswer $customFormAnswer)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('customFormAnswerId', 'hidden', array(
                'data' => $customFormAnswer->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }
}
