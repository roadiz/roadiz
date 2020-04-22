<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file FilterNodeSourcePathEvent.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Theme;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RequestContext;

/**
 * Class FilterNodeSourcePathEvent
 *
 * @package RZ\Roadiz\Core\Events
 * @deprecated
 */
class FilterNodeSourcePathEvent extends Event
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
     * FilterNodeSourcePathEvent constructor.
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
     * @return FilterNodeSourcePathEvent
     */
    public function setNodeSource(?NodesSources $nodeSource): FilterNodeSourcePathEvent
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
     * @return FilterNodeSourcePathEvent
     */
    public function setPath(?string $path): FilterNodeSourcePathEvent
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
     * @return FilterNodeSourcePathEvent
     */
    public function setParameters(?array $parameters): FilterNodeSourcePathEvent
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
     * @return FilterNodeSourcePathEvent
     */
    public function setComplete(bool $isComplete): FilterNodeSourcePathEvent
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
     * @return FilterNodeSourcePathEvent
     */
    public function setContainsScheme(bool $containsScheme): FilterNodeSourcePathEvent
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
     * @return FilterNodeSourcePathEvent
     */
    public function setForceLocaleWithUrlAlias(bool $forceLocaleWithUrlAlias): FilterNodeSourcePathEvent
    {
        $this->forceLocaleWithUrlAlias = $forceLocaleWithUrlAlias;

        return $this;
    }
}
