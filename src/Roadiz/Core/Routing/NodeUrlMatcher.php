<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * UrlMatcher which tries to grab Node and Translation
 * information for a route.
 */
class NodeUrlMatcher extends DynamicUrlMatcher
{
    /**
     * @var PathResolverInterface
     */
    protected PathResolverInterface $pathResolver;

    /**
     * @return array
     */
    public function getSupportedFormatExtensions(): array
    {
        return ['xml', 'json', 'pdf', 'html'];
    }

    /**
     * @return string
     */
    public function getDefaultSupportedFormatExtension(): string
    {
        return 'html';
    }

    /**
     * @param PathResolverInterface $pathResolver
     * @param RequestContext $context
     * @param ThemeResolverInterface $themeResolver
     * @param PreviewResolverInterface $previewResolver
     * @param Stopwatch|null $stopwatch
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        PathResolverInterface $pathResolver,
        RequestContext $context,
        ThemeResolverInterface $themeResolver,
        PreviewResolverInterface $previewResolver,
        Stopwatch $stopwatch = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($context, $themeResolver, $previewResolver, $stopwatch, $logger);
        $this->pathResolver = $pathResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('findTheme');
        }
        $this->theme = $this->themeResolver->findTheme($this->context->getHost());
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('findTheme');
        }

        $decodedUrl = rawurldecode($pathinfo);
        /*
         * Try nodes routes
         */
        return $this->matchNode($decodedUrl);
    }

    /**
     * @param string $decodedUrl
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function matchNode($decodedUrl): array
    {
        $resourceInfo = $this->pathResolver->resolvePath($decodedUrl, $this->getSupportedFormatExtensions());
        $nodeSource = $resourceInfo->getResource();

        if ($nodeSource !== null &&
            $nodeSource instanceof NodesSources &&
            !$nodeSource->getNode()->isHome()
        ) {
            $translation = $nodeSource->getTranslation();
            $nodeRouteHelper = new NodeRouteHelper(
                $nodeSource->getNode(),
                $this->theme,
                $this->previewResolver
            );

            if (!$this->previewResolver->isPreview() && !$translation->isAvailable()) {
                throw new ResourceNotFoundException();
            }

            if (false === $nodeRouteHelper->isViewable()) {
                throw new ResourceNotFoundException();
            }

            return [
                '_controller' => $nodeRouteHelper->getController() . '::' . $nodeRouteHelper->getMethod(),
                '_locale' => $resourceInfo->getLocale(),
                '_route' => RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                '_format' => $resourceInfo->getFormat(),
                'node' => $nodeSource->getNode(),
                RouteObjectInterface::ROUTE_OBJECT => $resourceInfo->getResource(),
                'translation' => $resourceInfo->getTranslation(),
                'theme' => $this->theme,
            ];
        }
        throw new ResourceNotFoundException();
    }
}
