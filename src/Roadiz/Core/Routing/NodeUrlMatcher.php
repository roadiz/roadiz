<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
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

        /*
         * Try with URL Aliases
         */
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('parseFromUrlAlias');
        }
        $node = $this->parseFromUrlAlias($tokens);
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('parseFromUrlAlias');
        }

        if ($node !== null) {
            /** @var Translation $translation */
            $translation = $node->getNodeSources()->first()->getTranslation();
            $nodeRouteHelper = new NodeRouteHelper(
                $node,
                $this->theme,
                $this->preview
            );

            if (!$this->preview && !$translation->isAvailable()) {
                throw new ResourceNotFoundException();
            }

            if (false === $nodeRouteHelper->isViewable()) {
                throw new ResourceNotFoundException();
            }

            return [
                '_controller' => $nodeRouteHelper->getController() . '::' . $nodeRouteHelper->getMethod(),
                '_locale' => $translation->getPreferredLocale(), //pass request locale to init translator
                '_route' => null,
                '_format' => $_format,
                'node' => $node,
                'translation' => $translation,
                'theme' => $this->theme,
            ];
        } else {
            /*
             * Try with node name
             */
            if (null !== $this->stopwatch) {
                $this->stopwatch->start('parseTranslation');
            }
            $translation = $this->parseTranslation($tokens);
            if (null !== $this->stopwatch) {
                $this->stopwatch->stop('parseTranslation');
            }

            if ($translation === null) {
                throw new ResourceNotFoundException();
            }

            if (null !== $this->stopwatch) {
                $this->stopwatch->start('parseNode');
            }
            $node = $this->parseNode($tokens, $translation);
            if (null !== $this->stopwatch) {
                $this->stopwatch->stop('parseNode');
            }

            /*
             * Prevent displaying home node using its nodeName
             */
            if ($node !== null && !$node->isHome()) {
                $nodeRouteHelper = new NodeRouteHelper(
                    $node,
                    $this->theme,
                    $this->preview
                );
                /*
                 * Try with nodeName
                 */
                if (false === $nodeRouteHelper->isViewable()) {
                    throw new ResourceNotFoundException();
                }
                $match = [
                    '_controller' => $nodeRouteHelper->getController() . '::' . $nodeRouteHelper->getMethod(),
                    '_route' => null,
                    '_format' => $_format,
                    'node' => $node,
                    'translation' => $translation,
                    'theme' => $this->theme,
                ];

                if (null !== $translation) {
                    $match['_locale'] = $translation->getPreferredLocale(); //pass request locale to init translator
                }

                return $match;
            }
        }
        throw new ResourceNotFoundException();
    }

    /**
     * Parse Node from UrlAlias.
     *
     * @param array $tokens
     *
     * @return Node
     */
    protected function parseFromUrlAlias(array &$tokens): ?Node
    {
        if (count($tokens) > 0) {
            $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);
            if ($identifier != '') {
                if ($this->preview === true) {
                    return $this->repository->findOneWithAlias($identifier);
                }
                return $this->repository->findOneWithAliasAndAvailableTranslation($identifier);
            }
        }
        return null;
    }

    /**
     * Parse URL searching nodeName.
     *
     * Cannot use securityAuthorizationChecker here as firewall
     * has not been hit yet.
     *
     * @param array $tokens
     * @param Translation $translation
     *
     * @return Node
     */
    protected function parseNode(array &$tokens, Translation $translation): ?Node
    {
        if (!empty($tokens[0])) {
            /*
             * If the only url token is not for language
             */
            if (count($tokens) > 1 || !in_array($tokens[0], Translation::getAvailableLocales())) {
                $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);

                if ($identifier !== null && $identifier != '') {
                    return $this->repository
                        ->findByNodeNameWithTranslation(
                            $identifier,
                            $translation
                        );
                }
            }
        }

        return null;
    }
}
