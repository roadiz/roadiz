<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Attributes;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\Attribute\Form\AttributeImportType;
use RZ\Roadiz\Attribute\Form\AttributeType;
use RZ\Roadiz\Attribute\Importer\AttributeImporter;
use RZ\Roadiz\Core\Entities\Attribute;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

class AttributeController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES');

        $listManager = $this->createEntityListManager(
            Attribute::class,
            [],
            ['code' => 'ASC']
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['items'] = $listManager->getEntities();

        return $this->render('attributes/list.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function exportAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES');

        $attributes = $this->get('em')->getRepository(Attribute::class)->findAll();
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                $attributes,
                'json',
                SerializationContext::create()->setGroups(['attribute'])
            ),
            JsonResponse::HTTP_OK,
            [
                'Content-Disposition' => sprintf('attachment; filename="%s"', 'attributes.json'),
            ],
            true
        );
    }

    /**
     * Import a Json file (.rzt) containing Attributes datas and fields.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES');

        $form = $this->createForm(AttributeImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();

            if ($file->isValid()) {
                $serializedData = file_get_contents($file->getPathname());

                $this->get(AttributeImporter::class)->import($serializedData);
                $this->get('em')->flush();
                return $this->redirect($this->generateUrl('attributesHomePage'));
            }
            $form->addError(new FormError($this->getTranslator()->trans('file.not_uploaded')));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('attributes/import.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES');

        $item = new Attribute();
        $item->setCode('new_attribute');

        $form = $this->createForm(AttributeType::class, $item, [
            'entityManager' => $this->get('em'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('em')->persist($item);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans('attribute.%name%.created', ['%name%' => $item->getCode()]);
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            if ($request->get('referer', '') !== '') {
                return $this->redirect($request->get('referer'));
            }
            return $this->redirect($this->generateUrl('attributesEditPage', ['id' => $item->getId()]));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('attributes/add.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function editAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES');

        /** @var Attribute $item */
        $item = $this->get('em')->find(Attribute::class, (int) $id);

        if ($item === null) {
            throw $this->createNotFoundException('Attribute does not exist.');
        }

        $form = $this->createForm(AttributeType::class, $item, [
            'entityManager' => $this->get('em'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('em')->flush();
                $msg = $this->getTranslator()->trans(
                    'attribute.%name%.updated',
                    ['%name%' => $item->getCode()]
                );
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }
            return $this->redirect($this->generateUrl('attributesEditPage', ['id' => $item->getId()]));
        }

        $this->assignation['item'] = $item;
        $this->assignation['form'] = $form->createView();

        return $this->render('attributes/edit.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_ATTRIBUTES_DELETE');

        /** @var Attribute $item */
        $item = $this->get('em')->find(Attribute::class, (int) $id);

        if ($item === null) {
            throw $this->createNotFoundException('Attribute does not exist.');
        }

        $form = $this->createForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('em')->remove($item);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans(
                    'attribute.%name%.deleted',
                    ['%name%' => $item->getCode()]
                );
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            return $this->redirect($this->generateUrl('attributesHomePage'));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render('attributes/delete.html.twig', $this->assignation);
    }
}
