<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Serializers\NodeSourceXlsxSerializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers\Nodes
 */
class ExportController extends RozierApp
{
    /**
     * Export all Node in a XLSX file (Excel).
     *
     * @param Request $request
     * @param int     $translationId
     * @param int|null     $parentNodeId
     *
     * @return Response
     */
    public function exportAllXlsxAction(Request $request, int $translationId, ?int $parentNodeId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        /*
         * Get translation
         */
        $translation = $this->get('em')
            ->find(Translation::class, $translationId);

        if (null === $translation) {
            $translation = $this->get('em')
                ->getRepository(Translation::class)
                ->findDefault();
        }
        $criteria = ["translation" => $translation];
        $order = ['node.nodeType' => 'ASC'];
        $filename = 'nodes-' . date("YmdHis") . '.' . $translation->getLocale() . '.xlsx';

        if (null !== $parentNodeId) {
            /** @var Node|null $parentNode */
            $parentNode = $this->get('em')->find(Node::class, $parentNodeId);
            if (null === $parentNode) {
                throw $this->createNotFoundException();
            }
            $criteria['node.parent'] = $parentNode;
            $filename = $parentNode->getNodeName() . '-' . date("YmdHis") . '.' . $translation->getLocale() . '.xlsx';
        }

        $sources = $this->get('em')
            ->getRepository(NodesSources::class)
            ->setDisplayingAllNodesStatuses(true)
            ->setDisplayingNotPublishedNodes(true)
            ->findBy($criteria, $order);

        $serializer = new NodeSourceXlsxSerializer($this->get('em'), $this->get('translator'), $this->get('urlGenerator'));
        $serializer->setOnlyTexts(true);
        $serializer->addUrls($request, $this->get('settingsBag')->get('force_locale'));
        $xlsx = $serializer->serialize($sources);

        $response = new Response(
            $xlsx,
            Response::HTTP_OK,
            []
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            )
        );

        $response->prepare($request);

        return $response;
    }
}
