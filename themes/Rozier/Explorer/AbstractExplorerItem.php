<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

abstract class AbstractExplorerItem implements ExplorerItemInterface
{
    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'classname' => $this->getAlternativeDisplayable(),
            'displayable' => $this->getDisplayable(),
            'editItem' => null,
            'thumbnail' => null
        ];
    }
}
