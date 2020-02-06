<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;

class SolrSearchResults
{
    /**
     * @var array
     */
    protected $response;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * SolrSearchResults constructor.
     *
     * @param array                  $response
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(array $response, EntityManagerInterface $entityManager)
    {
        $this->response = $response;
        $this->entityManager = $entityManager;
    }

    /**
     * @return int
     */
    public function getResultCount(): int
    {
        if (null !== $this->response &&
            isset($this->response['response']['numFound'])) {
            return (int) $this->response['response']['numFound'];
        }
        return 0;
    }

    /**
     * @return array
     */
    public function getResultItems(): array
    {
        if (null !== $this->response &&
            isset($this->response['response']['docs'])) {
            $doc = array_map(
                function ($item) {
                    $object = $this->getHydratedItem($item);
                    if (isset($this->response["highlighting"])) {
                        $key = 'object';
                        if ($object instanceof NodesSources) {
                            $key = 'nodeSource';
                        }
                        return [
                            $key => $object,
                            'highlighting' => $this->response['highlighting'][$item['id']],
                        ];
                    }
                    return $object;
                },
                $this->response['response']['docs']
            );
            return $doc;
        }
        return [];
    }

    /**
     * @param callable $callable
     *
     * @return array
     */
    public function map(callable $callable): array
    {
        return array_map($callable, $this->getResultItems());
    }

    /**
     * @param array $item
     *
     * @return array|object|null
     */
    protected function getHydratedItem(array $item)
    {
        if (isset($item[AbstractSolarium::TYPE_DISCRIMINATOR])) {
            switch ($item[AbstractSolarium::TYPE_DISCRIMINATOR]) {
                case SolariumNodeSource::DOCUMENT_TYPE:
                    return $this->entityManager->find(
                        NodesSources::class,
                        $item[SolariumNodeSource::IDENTIFIER_KEY]
                    );
                case SolariumDocumentTranslation::DOCUMENT_TYPE:
                    return $this->entityManager->find(
                        DocumentTranslation::class,
                        $item[SolariumDocumentTranslation::IDENTIFIER_KEY]
                    );
            }
        }

        return $item;
    }
}
