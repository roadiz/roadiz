<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * UrlMatcher which tries to grab Node and Translation
 * information for a route.
 */
class NodeUrlMatcher extends DynamicUrlMatcher
{
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

        $this->repository = $this->em->getRepository(Node::class);
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
        if (null === $this->theme) {
            throw new ResourceNotFoundException();
        }
        $tokens = explode('/', $decodedUrl);
        // Remove empty tokens (especially when a trailing slash is present)
        $tokens = array_values(array_filter($tokens));

        $_format = 'html';
        $nodeNamePattern = '[a-zA-Z0-9\-\_\.]+';
        $supportedFormats = $this->getSupportedFormatExtensions();
        $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);

        /*
         * Prevent searching nodes with special characters.
         */
        if (0 === preg_match('#'.$nodeNamePattern.'#', $identifier)) {
            throw new ResourceNotFoundException();
        }

        /*
         * Look for any supported format extension after last token.
         */
        if (0 !== preg_match('#^('.$nodeNamePattern.')\.('.implode('|', $supportedFormats).')$#', $identifier, $matches)) {
            $realIdentifier = $matches[1];
            $_format = $matches[2];
            // replace last token with real node-name without extension.
            $tokens[(int) (count($tokens) - 1)] = $realIdentifier;
        }

        if (null !== $this->stopwatch) {
            $this->stopwatch->start('parseTranslation');
        }
        $translation = $this->parseTranslation($tokens);
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('parseTranslation');
        }

        /*
         * Try with URL Aliases OR nodeName
         */
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('parseFromIdentifier');
        }
        $nodeSource = $this->parseFromIdentifier($tokens, $translation);
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('parseFromIdentifier');
        }

        if ($nodeSource !== null && !$nodeSource->getNode()->isHome()) {
            /** @var Translation $translation */
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
                '_locale' => $translation->getPreferredLocale(), //pass request locale to init translator
                '_route' => RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                '_format' => $_format,
                'node' => $nodeSource->getNode(),
                RouteObjectInterface::ROUTE_OBJECT => $nodeSource,
                'translation' => $translation,
                'theme' => $this->theme,
            ];
        }
        throw new ResourceNotFoundException();
    }

    /**
     * @param array            $tokens
     * @param Translation|null $translation
     *
     * @return NodesSources|null
     */
    protected function parseFromIdentifier(array &$tokens, ?Translation $translation = null): ?NodesSources
    {
        if (!empty($tokens[0])) {
            /*
             * If the only url token is not for language
             */
            if (count($tokens) > 1 || !in_array($tokens[0], Translation::getAvailableLocales())) {
                $identifier = mb_strtolower(strip_tags($tokens[(int) (count($tokens) - 1)]));
                if ($identifier !== null && $identifier != '') {
                    $array = $this->repository
                        ->findNodeTypeNameAndSourceIdByIdentifier(
                            $identifier,
                            $translation,
                            !$this->previewResolver->isPreview()
                        );
                    if (null !== $array) {
                        $fqcn = NodeType::getGeneratedEntitiesNamespace() . '\\NS' . ucwords($array['name']);
                        /** @var NodesSources|null $nodeSource */
                        $nodeSource = $this->em->getRepository($fqcn)->findOneBy([
                            'id' => $array['id']
                        ]);
                        return $nodeSource;
                    }
                }
            }
        }

        return null;
    }
}
