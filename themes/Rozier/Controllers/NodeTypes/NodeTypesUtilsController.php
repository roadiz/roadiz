<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 *
 * @file NodeTypesUtilsController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers\NodeTypes;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\CMS\Importers\NodeTypesImporter;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Documentation\Generators\DocumentationGenerator;
use RZ\Roadiz\Documentation\Generators\NodeTypeGenerator;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\Rozier\RozierApp;
use Twig_Error_Runtime;
use ZipArchive;

/**
 * {@inheritdoc}
 */
class NodeTypesUtilsController extends RozierApp
{
    /**
     * Export a Json file containing NodeType datas and fields.
     *
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return Response
     */
    public function exportJsonFileAction(Request $request, $nodeTypeId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');

        /** @var NodeType $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, (int) $nodeTypeId);

        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                $nodeType,
                'json',
                SerializationContext::create()->setGroups(['node_type', 'position'])
            ),
            JsonResponse::HTTP_OK,
            [
                'Content-Disposition' => sprintf('attachment; filename="%s"', $nodeType->getName() . '.json'),
            ],
            true
        );
    }

    /**
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function exportDocumentationAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');

        $documentationGenerator = new DocumentationGenerator($this->get('nodeTypesBag'), $this->get('translator'));

        $tmpfname = tempnam(sys_get_temp_dir(), date('Y-m-d-H-i-s') . '.zip');
        $zipArchive = new ZipArchive();
        $zipArchive->open($tmpfname, ZipArchive::CREATE);

        $zipArchive->addFromString(
            '_sidebar.md',
            $documentationGenerator->getNavBar()
        );

        /** @var NodeTypeGenerator $reachableTypeGenerator */
        foreach ($documentationGenerator->getReachableTypeGenerators() as $reachableTypeGenerator) {
            $zipArchive->addFromString(
                $reachableTypeGenerator->getPath(),
                $reachableTypeGenerator->getContents()
            );
        }

        /** @var NodeTypeGenerator $nonReachableTypeGenerator */
        foreach ($documentationGenerator->getNonReachableTypeGenerators() as $nonReachableTypeGenerator) {
            $zipArchive->addFromString(
                $nonReachableTypeGenerator->getPath(),
                $nonReachableTypeGenerator->getContents()
            );
        }

        $zipArchive->close();
        $response = new BinaryFileResponse($tmpfname);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'documentation-' . date('Y-m-d-H-i-s') . '.zip'
        );
        $response->prepare($request);

        return $response;
    }

    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportAllAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');

        $nodeTypes = $this->get('em')
            ->getRepository(NodeType::class)
            ->findAll();

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        $zipArchive = new ZipArchive();
        $tmpfname = tempnam(sys_get_temp_dir(), date('Y-m-d-H-i-s') . '.zip');
        $zipArchive->open($tmpfname, ZipArchive::CREATE);

        /** @var NodeType $nodeType */
        foreach ($nodeTypes as $nodeType) {
            $zipArchive->addFromString(
                $nodeType->getName() . '.json',
                $serializer->serialize(
                    $nodeType,
                    'json',
                    SerializationContext::create()->setGroups(['node_type', 'position'])
                )
            );
        }

        $zipArchive->close();
        $response = new BinaryFileResponse($tmpfname);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'nodetypes-' . date('Y-m-d-H-i-s') . '.zip'
        );
        $response->prepare($request);

        return $response;
    }

    /**
     * Import a Json file (.rzt) containing NodeType datas and fields.
     *
     * @param Request $request
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function importJsonFileAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODETYPES');

        $form = $this->buildImportJsonFileForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() &&
            $form->isValid() &&
            !empty($form['node_type_file'])) {
            $file = $form['node_type_file']->getData();

            if ($form->isSubmitted() && $file->isValid()) {
                $serializedData = file_get_contents($file->getPathname());

                if (null !== json_decode($serializedData)) {
                    $this->get(NodeTypesImporter::class)->import($serializedData);
                    $this->get('em')->flush();

                    /*
                     * Redirect to update schema page
                     */
                    return $this->redirect($this->generateUrl('nodeTypesSchemaUpdate'));
                }
                $form->addError(new FormError($this->getTranslator()->trans('file.format.not_valid')));
            } else {
                $form->addError(new FormError($this->getTranslator()->trans('file.not_uploaded')));
            }
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('node-types/import.html.twig', $this->assignation);
    }

    /**
     * @return Form
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->createFormBuilder()
                        ->add('node_type_file', FileType::class, [
                            'label' => 'nodeType.file',
                        ]);

        return $builder->getForm();
    }
}
