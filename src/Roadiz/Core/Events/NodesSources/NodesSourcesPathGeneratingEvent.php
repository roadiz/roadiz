<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events\NodesSources;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Theme;
use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\EventDispatcher\Event;

final class NodesSourcesPathGeneratingEvent extends Event
{
    /**
     * @var bool
     */
    protected $forceLocaleWithUrlAlias;
    /**
     * @var Theme|null
     */
    private $theme;
    /**
     * @var NodesSources|null
     */
    private $nodeSource;
    /**
     * @var array|null
     */
    private $parameters;
    /**
     * @var RequestContext|null
     */
    private $requestContext;
    /**
     * @var bool
     */
    private $forceLocale = false;
    /**
     * @var string|null
     */
    private $path;
    /**
     * @var bool Tells Node Router to prepend request context information to path or not.
     */
    private $isComplete = false;
    /**
     * @var bool
     */
    protected $containsScheme = false;

    /**
     * NodesSourcesPathGeneratingEvent constructor.
     *
     * @param Theme|null          $theme
     * @param NodesSources|null   $nodeSource
     * @param RequestContext|null $requestContext
     * @param array               $parameters
     * @param bool                $forceLocale
     * @param bool                $forceLocaleWithUrlAlias
     */
    public function __construct(
        ?Theme $theme,
        ?NodesSources $nodeSource,
        ?RequestContext $requestContext,
        array $parameters = [],
        bool $forceLocale = false,
        bool $forceLocaleWithUrlAlias = false
    ) {
        $this->theme = $theme;
        $this->nodeSource = $nodeSource;
        $this->requestContext = $requestContext;
        $this->forceLocale = $forceLocale;
        $this->parameters = $parameters;
        $this->forceLocaleWithUrlAlias = $forceLocaleWithUrlAlias;
    }

    /**
     * @return Theme|null
     */
    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    /**
     * @return NodesSources|null
     */
    public function getNodeSource(): ?NodesSources
    {
        return $this->nodeSource;
    }

    /**
     * @param NodesSources|null $nodeSource
     *
     * @return NodesSourcesPathGeneratingEvent
     */
    public function setNodeSource(?NodesSources $nodeSource): NodesSourcesPathGeneratingEvent
    {
        $this->nodeSource = $nodeSource;

        return $this;
    }

    /**
     * @return RequestContext|null
     */
    public function getRequestContext(): ?RequestContext
    {
        return $this->requestContext;
    }

    /**
     * @return bool
     */
    public function isForceLocale(): bool
    {
        return $this->forceLocale;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param string|null $path
     *
     * @return NodesSourcesPathGeneratingEvent
     */
    public function setPath(?string $path): NodesSourcesPathGeneratingEvent
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    /**
     * @param array|null $parameters
     *
     * @return NodesSourcesPathGeneratingEvent
     */
    public function setParameters(?array $parameters): NodesSourcesPathGeneratingEvent
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->isComplete;
    }

    /**
     * @param bool $isComplete
     *
     * @return NodesSourcesPathGeneratingEvent
     */
    public function setComplete(bool $isComplete): NodesSourcesPathGeneratingEvent
    {
        $this->isComplete = $isComplete;

        return $this;
    }

    /**
     * @return bool
     */
    public function containsScheme(): bool
    {
        return $this->containsScheme;
    }

    /**
     * @param bool $containsScheme
     *
     * @return NodesSourcesPathGeneratingEvent
     */
    public function setContainsScheme(bool $containsScheme): NodesSourcesPathGeneratingEvent
    {
        $this->containsScheme = $containsScheme;

        return $this;
    }

    /**
     * @return bool
     */
    public function isForceLocaleWithUrlAlias(): bool
    {
        return $this->forceLocaleWithUrlAlias;
    }

    /**
     * @param bool $forceLocaleWithUrlAlias
     *
     * @return NodesSourcesPathGeneratingEvent
     */
    public function setForceLocaleWithUrlAlias(bool $forceLocaleWithUrlAlias): NodesSourcesPathGeneratingEvent
    {
        $this->forceLocaleWithUrlAlias = $forceLocaleWithUrlAlias;

        return $this;
    }
}
