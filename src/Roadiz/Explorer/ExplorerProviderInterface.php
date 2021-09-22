<?php
declare(strict_types=1);

namespace RZ\Roadiz\Explorer;

use RZ\Roadiz\Core\ContainerAwareInterface;

interface ExplorerProviderInterface
{
    /**
     * @param mixed $item
     * @return ExplorerItemInterface|null
     */
    public function toExplorerItem($item): ?ExplorerItemInterface;

    /**
     * @param array $options Options (search, page, itemPerPage…)
     * @return ExplorerItemInterface[]
     */
    public function getItems($options = []): array;

    /**
     * @param array $options Options (search, page, itemPerPage…)
     * @return array
     */
    public function getFilters($options = []): array;

    /**
     * @param array $ids
     * @return ExplorerItemInterface[]
     */
    public function getItemsById($ids = []): array;

    /**
     * Check if object can be handled be current ExplorerProvider.
     *
     * @param mixed $item
     * @return bool
     */
    public function supports($item): bool;
}
