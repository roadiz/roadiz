<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\ContainerAwareInterface;

interface ExplorerProviderInterface extends ContainerAwareInterface
{
    /**
     * @param mixed $item
     * @return ExplorerItemInterface|null
     */
    public function toExplorerItem($item);

    /**
     * @param array $options Options (search, page, itemPerPage…)
     * @return ExplorerItemInterface[]
     */
    public function getItems($options = []);

    /**
     * @param array $options Options (search, page, itemPerPage…)
     * @return array
     */
    public function getFilters($options = []);

    /**
     * @param array $ids
     * @return ExplorerItemInterface[]
     */
    public function getItemsById($ids = []);

    /**
     * Check if object can be handled be current ExplorerProvider.
     *
     * @param mixed $item
     * @return boolean
     */
    public function supports($item);
}
