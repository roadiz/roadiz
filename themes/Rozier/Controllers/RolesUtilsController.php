<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\CMS\Importers\RolesImporter;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class RolesUtilsController extends RozierApp
{
    /**
     * Export a Role in a Json file
     *
     * @param Request $request
     * @param int $id
     *
     * @return Response
     */
    public function exportAction(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ROLES');

        /** @var Role|null $existingRole */
        $existingRole = $this->get('em')->find(Role::class, $id);

        if (null === $existingRole) {
            throw $this->createNotFoundException();
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                [$existingRole],
                'json',
                SerializationContext::create()->setGroups(['role'])
            ),
            JsonResponse::HTTP_OK,
            [
                'Content-Disposition' => sprintf('attachment; filename="%s"', 'role-' . $existingRole->getName() . '-' . date("YmdHis") . '.json'),
            ],
            true
        );
    }

    /**
     * Import a Json file containing Roles.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importJsonFileAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ROLES');

        $form = $this->buildImportJsonFileForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() &&
            $form->isValid() &&
            !empty($form['role_file'])) {
            $file = $form['role_file']->getData();

            if ($form->isSubmitted() && $file->isValid()) {
                $serializedData = file_get_contents($file->getPathname());

                if (null !== json_decode($serializedData)) {
                    if ($this->get(RolesImporter::class)->import($serializedData)) {
                        $msg = $this->getTranslator()->trans('role.imported');
                        $this->publishConfirmMessage($request, $msg);

                        $this->get('em')->flush();

                        // Clear result cache
                        $cacheDriver = $this->get('em')->getConfiguration()->getResultCacheImpl();
                        if ($cacheDriver !== null) {
                            $cacheDriver->deleteAll();
                        }

                        // redirect even if its null
                        return $this->redirect($this->generateUrl(
                            'rolesHomePage'
                        ));
                    }
                }
                $form->addError(new FormError($this->getTranslator()->trans('file.format.not_valid')));
            } else {
                $form->addError(new FormError($this->getTranslator()->trans('file.not_uploaded')));
            }
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('roles/import.html.twig', $this->assignation);
    }

    /**
     * @return FormInterface
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->createFormBuilder()
                        ->add('role_file', FileType::class, [
                            'label' => 'role.file',
                        ]);

        return $builder->getForm();
    }
}
