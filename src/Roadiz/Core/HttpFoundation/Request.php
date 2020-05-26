<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\HttpFoundation;

use RZ\Roadiz\Core\Entities\Theme;
use Symfony\Component\HttpFoundation\Request as BaseRequest;

/**
 * Roadiz Request extending Symfony to be able to store current
 * Theme in it.
 */
class Request extends BaseRequest
{
    /**
     * @var null|Theme
     */
    protected $theme = null;

    public function setTheme(Theme $theme = null): Request
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * @return Theme|null
     */
    public function getTheme(): ?Theme
    {
        return $this->theme;
    }
}
