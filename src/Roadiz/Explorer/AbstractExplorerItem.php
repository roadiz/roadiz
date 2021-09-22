<?php
declare(strict_types=1);

namespace RZ\Roadiz\Explorer;

abstract class AbstractExplorerItem implements ExplorerItemInterface
{
    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'classname' => $this->getAlternativeDisplayable() ?? '',
            'displayable' => $this->getDisplayable(),
            'editItem' => null,
            'thumbnail' => null
        ];
    }
}
