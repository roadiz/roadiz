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
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * UrlMatcher which tries to grab Node and Translation
 * informations for a route.
 */
class DynamicUrlMatcher extends UrlMatcher
{
    /** @var EntityManager */
    protected $em;
    /** @var Theme  */
    protected $theme;
    /** @var NodeRepository */
    protected $repository;
    /** @var Stopwatch  */
    protected $stopwatch;
    /** @var LoggerInterface */
    protected $logger;
    /** @var bool */
    protected $preview;
    /**
     * @var ThemeResolverInterface
     */
    protected $themeResolver;

    /**
     * @param RequestContext $context
     * @param EntityManager $em
     * @param ThemeResolverInterface $themeResolver
     * @param Stopwatch $stopwatch
     * @param LoggerInterface $logger
     * @param bool $preview
     */
    public function __construct(
        RequestContext $context,
        EntityManager $em,
        ThemeResolverInterface $themeResolver,
        Stopwatch $stopwatch = null,
        LoggerInterface $logger = null,
        $preview = false
    ) {
        $this->context = $context;
        $this->em = $em;
        $this->stopwatch = $stopwatch;
        $this->logger = $logger;
        $this->preview = $preview;
        $this->themeResolver = $themeResolver;
    }

    /**
     * Parse translation from URL tokens.
     *
     * @param array &$tokens
     *
     * @return Translation|null
     */
    protected function parseTranslation(&$tokens): ?Translation
    {
        /** @var TranslationRepository $repository */
        $repository = $this->em->getRepository(Translation::class);

        if (!empty($tokens[0])) {
            $firstToken = $tokens[0];
            $locale = strip_tags($firstToken);
            // First token is for language
            if ($locale !== null && $locale != '') {
                if ($this->preview === true) {
                    if (in_array($locale, $repository->getAllOverrideLocales())) {
                        return $repository->findOneByOverrideLocale($locale);
                    } elseif (in_array($locale, $repository->getAllLocales())) {
                        return $repository->findOneByLocale($locale);
                    }
                } else {
                    if (in_array($locale, $repository->getAvailableOverrideLocales())) {
                        return $repository->findOneByOverrideLocaleAndAvailable($locale);
                    } elseif (in_array($locale, $repository->getAvailableLocales())) {
                        return $repository->findOneByLocaleAndAvailable($locale);
                    }
                }
            }
        }

        return $repository->findDefault();
    }
}
