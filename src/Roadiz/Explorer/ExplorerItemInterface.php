<?php
declare(strict_types=1);

namespace RZ\Roadiz\Explorer;

interface ExplorerItemInterface
{
    /**
     * @return string|integer
     */
    public function getId();

    /**
     * @return string
     */
    public function getAlternativeDisplayable(): ?string;

    /**
     * @return string
     */
    public function getDisplayable(): string;

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
    public function toArray(): array;
}
