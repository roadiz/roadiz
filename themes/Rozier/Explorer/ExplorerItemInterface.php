<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

interface ExplorerItemInterface
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

    /**
     * Return a structured array of data.
     *
     * @return array
     */
    public function toArray();
}
