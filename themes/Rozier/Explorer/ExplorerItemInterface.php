<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use Themes\Rozier\Models\ModelInterface;

interface ExplorerItemInterface extends ModelInterface
{
    /**
     * @return string|integer
     */
    public function getId();

    /**
     * @return string
     */
    public function getAlternativeDisplayable();

    /**
     * @return string
     */
    public function getDisplayable();

    /**
     * Get original item.
     *
     * @return mixed
     */
    public function getOriginal();
}
