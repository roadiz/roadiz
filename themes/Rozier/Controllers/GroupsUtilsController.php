<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file GroupsUtilsController.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Group;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Handlers\GroupHandler;
use RZ\Renzo\Core\Serializers\GroupJsonSerializer;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class GroupsUtilsController extends RozierApp
{
    /**
     * Import a Json file (.rzt) containing Group datas and roles.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function importJsonFileAction(Request $request)
    {
        $form = $this->buildImportJsonFileForm();

        $form->handleRequest();

        if ($form->isValid() &&
            !empty($form['group_file'])) {

            $file = $form['group_file']->getData();

            if (UPLOAD_ERR_OK == $file['error']) {

                $serializedData = file_get_contents($file['tmp_name']);

                if (null !== json_decode($serializedData)) {

                    $group = GroupJsonSerializer::deserialize($serializedData);
                    $existingGroup = $this->getKernel()->em()
                        ->getRepository('RZ\Renzo\Core\Entities\Group')
                        ->findOneBy(array('name'=>$group->getName()));

                    if (null === $existingGroup) {

                        foreach ($group->getRolesEntities() as $role) {
                            /*
                             * then persist each role
                             */
                            $this->getKernel()->em()->persist($role);
                        }
                        $this->getKernel()->em()->flush();

                        /*
                         * New group.
                         *
                         * First persist group
                         */
                        $this->getKernel()->em()->persist($group);
                        // Flush before creating group's roles.

                        $msg = $this->getTranslator()->trans('group.imported.created');
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getLogger()->info($msg);

                    } else {
                        $existingGroup->getHandler()->diff($group);

                        $msg = $this->getTranslator()->trans('group.imported.updated');
                        $request->getSession()->getFlashBag()->add('confirm', $msg);
                        $this->getLogger()->info($msg);
                    }

                    $this->getKernel()->em()->flush();

                     // redirect even if its null
                    $response = new RedirectResponse(
                        $this->getKernel()->getUrlGenerator()->generate(
                            'groupsHomePage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();

                } else {
                    $msg = $this->getTranslator()->trans('file.format.not_valid');
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getLogger()->error($msg);

                    // redirect even if its null
                    $response = new RedirectResponse(
                        $this->getKernel()->getUrlGenerator()->generate(
                            'groupsImportPage'
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            } else {
                $msg = $this->getTranslator()->trans('file.not_uploaded');
                $request->getSession()->getFlashBag()->add('error', $msg);
                $this->getLogger()->error($msg);
            }
        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('groups/import.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('group_file', 'file');

        return $builder->getForm();
    }
}