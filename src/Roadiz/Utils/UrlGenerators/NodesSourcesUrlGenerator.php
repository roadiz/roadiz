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
use RZ\Roadiz\Core\HttpFoundation\Request;

class NodesSourcesUrlGenerator implements UrlGeneratorInterface
{
    protected $request;
    protected $nodeSource;

    /**
     *
     * @param RZ\Roadiz\Core\HttpFoundation\Request $request
     * @param RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     */
    public function __construct(Request $request, NodesSources $nodeSource)
    {
        $this->request = $request;
        $this->nodeSource = $nodeSource;
    }
    /**
     * Get a resource Url.
     *
     * @param boolean $absolute Use Url with domain name [default: false]
     * @return string
     */
    public function getUrl($absolute = false)
    {
        $host = $this->request->getBaseUrl();

        if ($absolute === true) {
            $host = $this->request->getResolvedBaseUrl();
        }

        if ($this->nodeSource->getNode()->isHome()
            || ($this->request->getTheme()
                && $this->request->getTheme()->getHomeNode() == $this->nodeSource->getNode())) {
            if ($this->nodeSource->getTranslation()->isDefaultTranslation()) {
                return $host . '/';
            } else {
                return $host .
                '/' . $this->nodeSource->getTranslation()->getLocale();
            }
        }

        $urlTokens = [];
        $urlTokens[] = $this->nodeSource->getHandler()->getIdentifier();

        $parent = $this->nodeSource->getHandler()->getParent();
        if ($parent !== null &&
            !$parent->getNode()->isHome()) {
            do {
                if ($parent->getNode()->isVisible()) {
                    $handler = $parent->getHandler();
                    $urlTokens[] = $handler->getIdentifier();
                }
                $parent = $parent->getHandler()->getParent();
            } while ($parent !== null && !$parent->getNode()->isHome());
        }

        /*
         * If using node-name, we must use shortLocale when current
         * translation is not the default one.
         */
        if ($urlTokens[0] == $this->nodeSource->getNode()->getNodeName() &&
            !$this->nodeSource->getTranslation()->isDefaultTranslation()) {
            $urlTokens[] = $this->nodeSource->getTranslation()->getLocale();
        }

        $urlTokens[] = $host;
        $urlTokens = array_reverse($urlTokens);

        return implode('/', $urlTokens);
    }
}
