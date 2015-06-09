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
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

/**
 * UrlMatcher which tries to grab Node and Translation
 * informations for a route.
 */
class DynamicUrlMatcher extends UrlMatcher
{
    protected $em;
    protected $theme = null;
    protected $repository = null;

    /**
     * @param RouteCollection $routes
     * @param RequestContext  $context
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(RequestContext $context, EntityManager $em)
    {
        $this->context = $context;
        $this->em = $em;
    }

    /**
     * Get Theme front controller class FQN.
     *
     * @return RZ\Roadiz\Core\Entities\Theme
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
     * @return RZ\Roadiz\Core\Entities\Translation
     */
    protected function parseTranslation(&$tokens)
    {
        $repository = $this->em->getRepository('RZ\Roadiz\Core\Entities\Translation');

        if (!empty($tokens[0])) {
            $firstToken = $tokens[0];
            $locale = strip_tags($firstToken);
            /*
             * First token is for language
             */
            if ($locale !== null && $locale != '') {
                if (in_array($firstToken, $repository->getAvailableOverrideLocales())) {
                    return $repository->findOneByOverrideLocaleAndAvailable($locale);
                } elseif (in_array($firstToken, $repository->getAvailableLocales())) {
                    return $repository->findOneByLocaleAndAvailable($locale);
                }
            }
        }

        return $repository->findDefault();
    }

    /**
     * Parse UrlAlias for Url tokens.
     *
     * @param array &$tokens
     *
     * @return RZ\Roadiz\Core\Entities\UrlAlias
     */
    protected function parseUrlAlias(&$tokens)
    {
        if (!empty($tokens[0])) {
            $locale = strip_tags($tokens[0]);

            $transRepository = $this->em->getRepository('RZ\Roadiz\Core\Entities\Translation');
            /*
             * If the only url token if for language, return no url alias !
             */
            if (count($tokens) === 1 &&
                (in_array($locale, $transRepository->getAvailableOverrideLocales()) ||
                    in_array($locale, $transRepository->getAvailableLocales()))
            ) {
                return null;
            } else {
                $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);

                if ($identifier != '') {
                    return $this->em->getRepository('RZ\Roadiz\Core\Entities\UrlAlias')
                                ->findOneBy(['alias' => $identifier]);
                }
            }
        }

        return null;
    }
}
