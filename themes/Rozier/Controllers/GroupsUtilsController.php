<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\CMS\Importers\GroupsImporter;
use RZ\Roadiz\Core\Entities\Group;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class GroupsUtilsController extends RozierApp
{
    /**
     * Export all Group data and roles in a Json file (.json).
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportAllAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        $existingGroup = $this->get('em')
                              ->getRepository(Group::class)
                              ->findAll();

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                $existingGroup,
                'json',
                SerializationContext::create()->setGroups(['group'])
            ),
            JsonResponse::HTTP_OK,
            [
                'Content-Disposition' => sprintf('attachment; filename="%s"', 'group-all-' . date("YmdHis") . '.json'),
            ],
            true
        );
    }

    /**
     * Export a Group in a Json file (.json).
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function exportAction(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        $existingGroup = $this->get('em')->find(Group::class, $id);

        if (null === $existingGroup) {
            throw $this->createNotFoundException();
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                [$existingGroup], // need to wrap in array
                'json',
                SerializationContext::create()->setGroups(['group'])
            ),
            JsonResponse::HTTP_OK,
            [
                'Content-Disposition' => sprintf('attachment; filename="%s"', 'group-' . $existingGroup->getName() . '-' . date("YmdHis") . '.json'),
            ],
            true
        );
    }

    /**
     * Import a Json file (.rzt) containing Group datas and roles.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importJsonFileAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_GROUPS');

        $form = $this->buildImportJsonFileForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() &&
            $form->isValid() &&
            !empty($form['group_file'])) {
            /** @var UploadedFile $file */
            $file = $form['group_file']->getData();

            if ($form->isSubmitted() && $file->isValid()) {
                $serializedData = file_get_contents($file->getPathname());

                if (null !== json_decode($serializedData)) {
                    $this->get(GroupsImporter::class)->import($serializedData);
                    $this->get('em')->flush();

                    $msg = $this->getTranslator()->trans('group.imported.updated');
                    $this->publishConfirmMessage($request, $msg);

                    // redirect even if its null
                    return $this->redirect($this->generateUrl(
                        'groupsHomePage'
                    ));
                }
                $form->addError(new FormError($this->getTranslator()->trans('file.format.not_valid')));
            } else {
                $form->addError(new FormError($this->getTranslator()->trans('file.not_uploaded')));
            }
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('groups/import.html.twig', $this->assignation);
    }

    /**
     * @return FormInterface
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->createFormBuilder()
                        ->add('group_file', FileType::class, [
                            'label' => 'group.file',
                        ]);

        return $builder->getForm();
    }
}
