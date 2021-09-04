<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\HttpFoundation\Request;
use RZ\Roadiz\Core\Routing\NodesSourcesPathAggregator;

/**
 * Do not extend this class, use NodesSourcesPathGeneratingEvent::class event
 *
 * @package RZ\Roadiz\Utils\UrlGenerators
 */
final class NodesSourcesUrlGenerator
{
    protected ?Request $request;
    protected ?NodesSources $nodeSource;
    protected bool $forceLocale;
    protected bool $forceLocaleWithUrlAlias;
    protected NodesSourcesPathAggregator $pathAggregator;

    /**
     * @param NodesSourcesPathAggregator $pathAggregator
     * @param Request|null               $request
     * @param NodesSources|null          $nodeSource
     * @param bool                       $forceLocale
     * @param bool                       $forceLocaleWithUrlAlias
     */
    public function __construct(
        NodesSourcesPathAggregator $pathAggregator,
        Request $request = null,
        NodesSources $nodeSource = null,
        bool $forceLocale = false,
        bool $forceLocaleWithUrlAlias = false
    ) {
        $this->pathAggregator = $pathAggregator;
        $this->request = $request;
        $this->nodeSource = $nodeSource;
        $this->forceLocale = $forceLocale;
        $this->forceLocaleWithUrlAlias = $forceLocaleWithUrlAlias;
    }

    /**
     * @param NodesSources $nodeSource
     * @return bool
     */
    protected function isNodeSourceHome(NodesSources $nodeSource): bool
    {
        if ($nodeSource->getNode()->isHome()) {
            return true;
        }

        return false;
    }

    /**
     * Return a NodesSources url without hostname and without
     * root folder.
     *
     * It returns a relative url to Roadiz, not relative to your server root.
     *
     * @param Theme|null $theme
     * @param array $parameters
     *
     * @return string
     */
    public function getNonContextualUrl(?Theme $theme = null, array $parameters = []): string
    {
        if (null !== $this->nodeSource) {
            if ($this->isNodeSourceHome($this->nodeSource)) {
                if ($this->nodeSource->getTranslation()->isDefaultTranslation() &&
                    false === $this->forceLocale) {
                    return '';
                } else {
                    return $this->nodeSource->getTranslation()->getPreferredLocale();
                }
            }

            $path = $this->pathAggregator->aggregatePath($this->nodeSource, $parameters);

            /*
             * If using node-name, we must use shortLocale when current
             * translation is not the default one.
             */
            if ($this->urlNeedsLocalePrefix($this->nodeSource)) {
                $path = $this->nodeSource->getTranslation()->getPreferredLocale() . '/' . $path;
            }

            if (null !== $theme && $theme->getRoutePrefix() != '') {
                $path = $theme->getRoutePrefix() . '/' . $path;
            }
            /*
             * Add non default format at the path end.
             */
            if (isset($parameters['_format']) && in_array($parameters['_format'], ['xml', 'json', 'pdf'])) {
                $path .= '.' . $parameters['_format'];
            }

            return $path;
        } else {
            throw new \RuntimeException("Cannot generate Url for a NULL NodesSources", 1);
        }
    }

    /**
     * @param NodesSources $nodesSources
     *
     * @return bool
     */
    protected function useUrlAlias(NodesSources $nodesSources): bool
    {
        if ($nodesSources->getIdentifier() !== $nodesSources->getNode()->getNodeName()) {
            return true;
        }

        return false;
    }

    /**
     * @param NodesSources $nodesSources
     *
     * @return bool
     */
    protected function urlNeedsLocalePrefix(NodesSources $nodesSources): bool
    {
        /*
         * Needs a prefix only if translation is not default AND nodeSource does not have an Url alias
         * for this translation.
         * Of course we force prefix if admin said so…
         * Or we can force prefix only when we use urlAliases
         */
        if ((
                !$this->useUrlAlias($nodesSources) &&
                !$nodesSources->getTranslation()->isDefaultTranslation()
            ) ||
            (
                $this->useUrlAlias($nodesSources) &&
                !$nodesSources->getTranslation()->isDefaultTranslation() &&
                true === $this->forceLocaleWithUrlAlias
            ) ||
            true === $this->forceLocale
        ) {
            return true;
        }

        return false;
    }
}
