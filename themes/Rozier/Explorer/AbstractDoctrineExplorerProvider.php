<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Core\ListManagers\EntityListManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @package Themes\Rozier\Explorer
 */
abstract class AbstractDoctrineExplorerProvider extends AbstractExplorerProvider
{
    /**
     * @return string
     */
    abstract protected function getProvidedClassname(): string;

    /**
     * @return array
     */
    abstract protected function getDefaultCriteria(): array;

    /**
     * @return array
     */
    abstract protected function getDefaultOrdering(): array;

    /**
     * @param array $options
     *
     * @return EntityListManagerInterface
     */
    protected function doFetchItems(array $options = []): EntityListManagerInterface
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $listManager = new EntityListManager(
            $this->get('requestStack')->getCurrentRequest(),
            $this->get('em'),
            $this->getProvidedClassname(),
            $this->getDefaultCriteria(),
            $this->getDefaultOrdering()
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setItemPerPage($this->options['itemPerPage']);
        $listManager->handle();
        $listManager->setPage($this->options['page']);

        return $listManager;
    }
    /**
     * @inheritDoc
     */
    public function getItems($options = [])
    {
        $listManager = $this->doFetchItems($options);

        $items = [];
        foreach ($listManager->getEntities() as $entity) {
            $items[] = $this->toExplorerItem($entity);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function getFilters($options = [])
    {
        $listManager = $this->doFetchItems($options);

        return $listManager->getAssignation();
    }

    /**
     * @inheritDoc
     */
    public function getItemsById($ids = [])
    {
        if (is_array($ids) && count($ids) > 0) {
            $entities = $this->get('em')->getRepository($this->getProvidedClassname())->findBy([
                'id' => $ids
            ]);

            $items = [];
            foreach ($entities as $entity) {
                $items[] = $this->toExplorerItem($entity);
            }

            return $items;
        }

        return [];
    }
}
