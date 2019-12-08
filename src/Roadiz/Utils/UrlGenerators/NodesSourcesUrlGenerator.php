<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file NodesSourcesUrlGenerator.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\UrlGenerators;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\HttpFoundation\Request;

class NodesSourcesUrlGenerator implements UrlGeneratorInterface
{
    protected $request;
    protected $nodeSource;
    protected $forceLocale;

    /**
     *
     * @param Request $request
     * @param NodesSources $nodeSource
     * @param bool $forceLocale
     */
    public function __construct(
        Request $request = null,
        NodesSources $nodeSource = null,
        $forceLocale = false
    ) {
        $this->request = $request;
        $this->nodeSource = $nodeSource;
        $this->forceLocale = $forceLocale;
    }


    /**
     * Get a resource Url.
     *
     * @param boolean $absolute Use Url with domain name [default: false]
     * @param string $canonicalSchemeAuthority Override protocol, host and port to generate Url. Need absolute to true
     * @return string
     * @deprecated Do not use this method directly to generate NodesSources URI but ChainRouter::generate method.
     */
    public function getUrl(bool $absolute = false, $canonicalSchemeAuthority = ''): string
    {
        trigger_error('NodesSourcesUrlGenerator::getUrl method is deprecated. Use ChainRouter::generate method instead.', E_USER_DEPRECATED);

        if (null !== $this->request) {
            $schemeAuthority = '';

            if ($absolute === true) {
                if (!empty($canonicalSchemeAuthority)) {
                    $schemeAuthority = trim($canonicalSchemeAuthority);
                } else {
                    $schemeAuthority = $this->request->getSchemeAndHttpHost();
                }
            }

            return $schemeAuthority .
            $this->request->getBaseUrl() .
            '/' .
            $this->getNonContextualUrl($this->request->getTheme());
        } else {
            return '/' . $this->getNonContextualUrl();
        }
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
     * @param Theme $theme
     * @param array $parameters
     *
     * @return string
     */
    public function getNonContextualUrl(Theme $theme = null, $parameters = []): string
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

            $urlTokens = [];
            if (isset($parameters['_format']) && in_array($parameters['_format'], ['xml', 'json', 'pdf'])) {
                $urlTokens[] = $this->nodeSource->getIdentifier() . '.' . $parameters['_format'];
            } else {
                $urlTokens[] = $this->nodeSource->getIdentifier();
            }

            $parent = $this->nodeSource->getParent();
            if ($parent !== null && !$parent->getNode()->isHome()) {
                do {
                    if ($parent->getNode()->isVisible()) {
                        $urlTokens[] = $parent->getIdentifier();
                    }
                    $parent = $parent->getParent();
                } while ($parent !== null && !$parent->getNode()->isHome());
            }

            /*
             * If using node-name, we must use shortLocale when current
             * translation is not the default one.
             */
            if ($this->urlNeedsLocalePrefix($this->nodeSource, $this->forceLocale)) {
                $urlTokens[] = $this->nodeSource->getTranslation()->getPreferredLocale();
            }

            $urlTokens = array_reverse($urlTokens);

            if (null !== $theme && $theme->getRoutePrefix() != '') {
                return $theme->getRoutePrefix() . '/' . implode('/', $urlTokens);
            }

            return implode('/', $urlTokens);
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
     * @param bool         $forceLocale
     *
     * @return bool
     */
    protected function urlNeedsLocalePrefix(NodesSources $nodesSources, bool $forceLocale): bool
    {
        /*
         * Needs a prefix only if translation is not default AND nodeSource does not have an Url alias
         * for this translation.
         * Of course we force prefix if admin said so…
         */
        if ((!$this->useUrlAlias($nodesSources) && !$nodesSources->getTranslation()->isDefaultTranslation()) ||
            true === $forceLocale) {
            return true;
        }

        return false;
    }
}
