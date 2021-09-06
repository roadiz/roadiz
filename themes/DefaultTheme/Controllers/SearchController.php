<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Controllers;

use GeneratedNodeSources\NSPage;
use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Events\QueryBuilder\QueryBuilderSelectEvent;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\DefaultTheme\DefaultThemeApp;

class SearchController extends DefaultThemeApp
{
    /**
     * Default action for searching a Page source.
     *
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     */
    public function defaultAction(
        Request $request,
        string $_locale = "en"
    ) {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);

        if (!$request->query->has('query') || $request->query->get('query') == '') {
            throw new ResourceNotFoundException();
        }

        $callable = function (QueryBuilderSelectEvent $event) {
            if ($event->supports(NodesSources::class) || $event->supports(NSPage::class)) {
                $qb = $event->getQueryBuilder();
                $qb->andWhere($qb->expr()->neq($qb->expr()->lower('ns.title'), ':neq'));
                $qb->setParameter('neq', 'FOOBAR');
            }
        };
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->get('dispatcher');
        $eventDispatcher->addListener(
            QueryBuilderSelectEvent::class,
            $callable
        );

        /** @var NodeSourceSearchHandlerInterface|null $searchHandler */
        $searchHandler = $this->get(NodeSourceSearchHandlerInterface::class);
        if (null !== $searchHandler) {
            /*
             * Use Apache Solr when available
             */
            $searchHandler->boostByPublicationDate();
            $searchResponse = $searchHandler->search(
                $request->query->get('query'), # Use ?query query parameter to search with
                [
                    'translation' => $translation,
                ], # a simple criteria array to filter search results
                10, # result count
                true # Search in tags too
            );
            $nodeSources = $searchResponse->getResultItems();
        } else {
            /*
             * Use simple search over title and meta fields.
             */
            /** @var NodeSourceApi $nodeSourceApi */
            $nodeSourceApi = $this->get('nodeSourceApi');
            /** @var NodesSources[] $nodeSources */
            $nodeSources = $nodeSourceApi->searchBy(
                $request->query->get('query'),
                10,
                [
                    $this->get('nodeTypesBag')->get('Page')
                ],
                true,
                [
                    'node.nodeType.reachable' => true,
                ]
            );
        }

        $this->assignation['nodeSources'] = $nodeSources;
        $this->assignation['query'] = $request->query->get('query');

        $eventDispatcher->removeListener(
            QueryBuilderSelectEvent::class,
            $callable
        );

        $response = $this->render('pages/search.html.twig', $this->assignation);
        return $this->makeResponseCachable($request, $response, 10);
    }
}
