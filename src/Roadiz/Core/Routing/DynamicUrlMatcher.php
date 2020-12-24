<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * UrlMatcher which tries to grab Node and Translation
 * information for a route.
 */
abstract class DynamicUrlMatcher extends UrlMatcher
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;
    /**
     * @var Theme
     */
    protected $theme;
    /**
     * @var NodeRepository
     */
    protected $repository;
    /**
     * @var Stopwatch
     */
    protected $stopwatch;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ThemeResolverInterface
     */
    protected $themeResolver;
    /**
     * @var PreviewResolverInterface
     */
    protected $previewResolver;

    /**
     * @param RequestContext $context
     * @param EntityManagerInterface $em
     * @param ThemeResolverInterface $themeResolver
     * @param PreviewResolverInterface $previewResolver
     * @param Stopwatch|null $stopwatch
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        RequestContext $context,
        EntityManagerInterface $em,
        ThemeResolverInterface $themeResolver,
        PreviewResolverInterface $previewResolver,
        Stopwatch $stopwatch = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct(new RouteCollection(), $context);
        $this->em = $em;
        $this->stopwatch = $stopwatch;
        $this->logger = $logger;
        $this->themeResolver = $themeResolver;
        $this->previewResolver = $previewResolver;
    }

    /**
     * Parse translation from URL tokens even if it is not available yet.
     *
     * @param array $tokens
     *
     * @return Translation|null
     */
    protected function parseTranslation(array &$tokens): ?Translation
    {
        /** @var TranslationRepository $repository */
        $repository = $this->em->getRepository(Translation::class);

        if (!empty($tokens[0])) {
            $firstToken = $tokens[0];
            $locale = mb_strtolower(strip_tags((string) $firstToken));
            // First token is for language
            if ($locale !== null && $locale != '') {
                $translation = $repository->findOneByLocaleOrOverrideLocale($locale);
                if (null !== $translation) {
                    return $translation;
                }
            }
        }

        return $repository->findDefault();
    }
}
