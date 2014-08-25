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
     * Export a Json file containing Groups datas and roles.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $groupId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function exportJsonFileAction(Request $request, $groupId)
    {
        $group = $this->getKernel()->em()
            ->find('RZ\Renzo\Core\Entities\Group', (int) $groupId);

        $response =  new Response(
            $group->getSerializer()->serialize(),
            Response::HTTP_OK,
            array()
        );

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $group->getName() . '.rzt')); // Rezo-Zero Type

        $response->prepare($request);

        return $response;
    }

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

                        $this->getKernel()->em()->persist($group);
                        $this->getKernel()->em()->flush();

                        foreach ($group->getRoles() as $role) {
                            /*
                             * then persist each field
                             */
                            $group->addRoles($role);
                            $this->getKernel()->em()->persist($role);
                        }

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
                } else {
                    $msg = $this->getTranslator()->trans('file.format.not_valid');
                    $request->getSession()->getFlashBag()->add('error', $msg);
                    $this->getLogger()->error($msg);
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