<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Theme;
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
    protected ?Theme $theme;
    protected ?Stopwatch $stopwatch;
    protected ?LoggerInterface $logger;
    protected ThemeResolverInterface $themeResolver;
    protected PreviewResolverInterface $previewResolver;

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
