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
 * @file MixedUrlMatcher.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Routing;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Extends compiled UrlMatcher to add a dynamic routing feature which deals
 * with NodesSources URL.
 */
class MixedUrlMatcher extends \GlobalUrlMatcher
{
    protected $dynamicUrlMatcher;
    protected $installMode;
    protected $stopwatch;

    /**
     * @param RequestContext  $context
     * @param DynamicUrlMatcher $dynamicUrlMatcher
     * @param boolean $installMode
     */
    public function __construct(
        RequestContext $context,
        DynamicUrlMatcher $dynamicUrlMatcher,
        $installMode = false,
        Stopwatch $stopwatch = null
    ) {
        $this->context = $context;
        $this->dynamicUrlMatcher = $dynamicUrlMatcher;
        $this->installMode = $installMode;
        $this->stopwatch = $stopwatch;
    }
    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        if (null !== $this->stopwatch) { $this->stopwatch->start('matchingRoute'); }

        $matched = null;
        if (true === $this->installMode) {
            // No node controller matching in install mode
            $matched = parent::match($pathinfo);
        }

        try {
            /*
             * Try STATIC routes
             */
            $matched = parent::match($pathinfo);

        } catch (ResourceNotFoundException $e) {
            /*
             * Try dynamic routes
             */
            if (null !== $this->stopwatch) { $this->stopwatch->start('matchingDynamicRoute'); }
            $matched = $this->dynamicUrlMatcher->match($pathinfo);
            if (null !== $this->stopwatch) { $this->stopwatch->stop('matchingDynamicRoute'); }
        }

        if (null !== $this->stopwatch) { $this->stopwatch->stop('matchingRoute'); }
        return $matched;
    }
}
