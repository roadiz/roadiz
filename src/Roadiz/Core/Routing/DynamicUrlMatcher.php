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
     * @var Theme
     */
    protected $theme;
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
     * @param ThemeResolverInterface $themeResolver
     * @param PreviewResolverInterface $previewResolver
     * @param Stopwatch|null $stopwatch
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        RequestContext $context,
        ThemeResolverInterface $themeResolver,
        PreviewResolverInterface $previewResolver,
        Stopwatch $stopwatch = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct(new RouteCollection(), $context);
        $this->stopwatch = $stopwatch;
        $this->logger = $logger;
        $this->themeResolver = $themeResolver;
        $this->previewResolver = $previewResolver;
    }
}
