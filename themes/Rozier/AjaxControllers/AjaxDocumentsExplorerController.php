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
 * @file AjaxDocumentsExplorerController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\AjaxControllers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Themes\Rozier\Models\DocumentModel;

/**
 * {@inheritdoc}
 */
class AjaxDocumentsExplorerController extends AbstractAjaxController
{
    public static $thumbnailArray = null;
    /**
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        /*
         * Prevent raw document to show in explorer.
         */
        $arrayFilter = [
            'raw' => false,
        ];

        if ($request->get('folderId') > 0) {
            $folder = $this->get('em')
                        ->find(
                            Folder::class,
                            $request->get('folderId')
                        );

            $arrayFilter['folders'] = [$folder];
        }
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            Document::class,
            $arrayFilter,
            [
                'createdAt' => 'DESC'
            ]
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setItemPerPage(30);
        $listManager->handle();

        $documents = $listManager->getEntities();
        $documentsArray = $this->normalizeDocuments($documents);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'documents' => $documentsArray,
            'documentsCount' => count($documents),
            'filters' => $listManager->getAssignation(),
            'trans' => $this->getTrans(),
        ];

        if ($request->get('folderId') > 0) {
            $responseArray['filters'] = array_merge($responseArray['filters'], [
                'folderId' => $request->get('folderId')
            ]);
        }

        return new JsonResponse(
            $responseArray,
            Response::HTTP_OK
        );
    }

    /**
     * Get a Document list from an array of id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCUMENTS');

        if (!$request->query->has('ids') || !is_array($request->query->get('ids'))) {
            throw new InvalidParameterException('Ids should be provided within an array');
        }

        $cleanDocumentIds = array_filter($request->query->get('ids'));

        /** @var EntityManager $em */
        $em = $this->get('em');
        $documents = $em->getRepository(Document::class)->findBy([
            'id' => $cleanDocumentIds,
            'raw' => false,
        ]);

        // Sort array by ids given in request
        $documents = $this->sortIsh($documents, $cleanDocumentIds);
        $documentsArray = $this->normalizeDocuments($documents);

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'documents' => $documentsArray,
            'trans' => $this->getTrans()
        ];

        return new JsonResponse(
            $responseArray,
            Response::HTTP_OK
        );
    }

    /**
     * Normalize response Document list result.
     *
     * @param $documents
     * @return array
     */
    private function normalizeDocuments($documents)
    {
        $documentsArray = [];

        /** @var Document $doc */
        foreach ($documents as $doc) {
            $documentModel = new DocumentModel($doc, $this->getContainer());
            $documentsArray[] = $documentModel->toArray();
        }

        return $documentsArray;
    }

    /**
     * Get an array of translations.
     *
     * @return array
     */
    private function getTrans()
    {
        return [
            'editDocument' => $this->getTranslator()->trans('edit.document'),
            'unlinkDocument' => $this->getTranslator()->trans('unlink.document'),
            'linkDocument' => $this->getTranslator()->trans('link.document'),
            'moreItems' => $this->getTranslator()->trans('more.documents')
        ];
    }
}

AjaxDocumentsExplorerController::$thumbnailArray = [
    "fit" => "40x40",
    "quality" => 50,
    "inline" => false,
];
