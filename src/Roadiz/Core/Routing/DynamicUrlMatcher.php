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
 * @file DynamicUrlMatcher.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Routing;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * UrlMatcher which tries to grab Node and Translation
 * informations for a route.
 */
class DynamicUrlMatcher extends UrlMatcher
{
    protected $em;
    protected $theme = null;
    protected $repository = null;
    protected $stopwatch = null;
    protected $logger = null;

    /**
     * @param RequestContext $context
     * @param EntityManager $em
     * @param Stopwatch $stopwatch
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestContext $context,
        EntityManager $em,
        Stopwatch $stopwatch = null,
        LoggerInterface $logger = null
    ) {
        $this->context = $context;
        $this->em = $em;
        $this->stopwatch = $stopwatch;
        $this->logger = $logger;
    }

    /**
     * Get Theme front controller class FQN.
     *
     * @return \RZ\Roadiz\Core\Entities\Theme
     */
    protected function findTheme()
    {
        $host = $this->context->getHost();
        /*
         * First we look for theme according to hostname.
         */
        $theme = $this->em->getRepository('RZ\Roadiz\Core\Entities\Theme')
            ->findAvailableNonStaticFrontendWithHost($host);

        /*
         * If no theme for current host, we look for
         * any frontend available theme.
         */
        if (null === $theme) {
            $theme = $this->em->getRepository('RZ\Roadiz\Core\Entities\Theme')
                ->findFirstAvailableNonStaticFrontend();
        }

        return $theme;
    }

    /**
     * Parse translation from URL tokens.
     *
     * @param array &$tokens
     *
     * @return \RZ\Roadiz\Core\Entities\Translation
     */
    protected function parseTranslation(&$tokens)
    {
        $repository = $this->em->getRepository('RZ\Roadiz\Core\Entities\Translation');

        if (!empty($tokens[0])) {
            $firstToken = $tokens[0];
            $locale = strip_tags($firstToken);
            // First token is for language
            if ($locale !== null && $locale != '') {
                if (in_array($locale, $repository->getAvailableOverrideLocales())) {
                    return $repository->findOneByOverrideLocaleAndAvailable($locale);
                } elseif (in_array($locale, $repository->getAvailableLocales())) {
                    return $repository->findOneByLocaleAndAvailable($locale);
                }
            }
        }

        return $repository->findDefault();
    }
}
