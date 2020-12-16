<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\Folder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package Themes\Rozier\AjaxControllers
 */
class AjaxFoldersExplorerController extends AbstractAjaxController
{
    public static $thumbnailArray = null;
    /**
     * @param Request $request
     *
     * @return Response JSON response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCUMENTS');

        $folders = $this->get('em')
                        ->getRepository(Folder::class)
                        ->findBy(
                            [
                                'parent' => null,
                            ],
                            [
                                'position' => 'ASC',
                            ]
                        );

        $responseArray = [
            'status' => 'confirm',
            'statusCode' => 200,
            'folders' => $this->recurseFolders($folders),
        ];

        return new JsonResponse(
            $responseArray
        );
    }

    protected function recurseFolders($folders = null)
    {
        $foldersArray = [];
        if ($folders !== null) {
            /** @var Folder $folder */
            foreach ($folders as $folder) {
                $children = $this->recurseFolders($folder->getChildren());
                $foldersArray[] = [
                    'id' => $folder->getId(),
                    'name' => $folder->getName(),
                    'folderName' => $folder->getFolderName(),
                    'children' => $children,
                ];
            }
        }

        return $foldersArray;
    }
}
