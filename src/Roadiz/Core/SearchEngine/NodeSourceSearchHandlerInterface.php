<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine;

interface NodeSourceSearchHandlerInterface extends SearchHandlerInterface
{
    public function boostByPublicationDate(): NodeSourceSearchHandlerInterface;
    public function boostByUpdateDate(): NodeSourceSearchHandlerInterface;
    public function boostByCreationDate(): NodeSourceSearchHandlerInterface;
}
