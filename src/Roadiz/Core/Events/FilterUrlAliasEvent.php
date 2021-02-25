<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\UrlAlias;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @package RZ\Roadiz\Core\Events
 */
abstract class FilterUrlAliasEvent extends Event
{
    protected $urlAlias;

    public function __construct(UrlAlias $urlAlias)
    {
        $this->urlAlias = $urlAlias;
    }

    public function getUrlAlias()
    {
        return $this->urlAlias;
    }
}
