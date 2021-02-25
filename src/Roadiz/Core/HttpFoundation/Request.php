<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\HttpFoundation;

use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Preview\PreviewAwareInterface;
use Symfony\Component\HttpFoundation\Request as BaseRequest;

/**
 * Roadiz Request extending Symfony to be able to store current
 * Theme in it.
 */
class Request extends BaseRequest implements PreviewAwareInterface
{
    /**
     * @var null|Theme
     */
    protected ?Theme $theme = null;

    /**
     * @var bool Preview mode, override Kernel-wide parameter
     */
    protected bool $preview = false;

    /**
     * @param Theme|null $theme
     * @return $this
     */
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

    /**
     * @return bool
     */
    public function isPreview(): bool
    {
        return $this->preview;
    }

    /**
     * @param bool $preview
     * @return Request
     */
    public function setPreview(bool $preview)
    {
        $this->preview = $preview;
        return $this;
    }
}
