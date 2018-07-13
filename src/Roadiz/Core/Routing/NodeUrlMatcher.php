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
 * @file NodeUrlMatcher.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * UrlMatcher which tries to grab Node and Translation
 * informations for a route.
 */
class NodeUrlMatcher extends DynamicUrlMatcher
{
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
        if (false !== $ret = $this->matchNode($decodedUrl)) {
            if (null !== $this->logger) {
                $this->logger->debug('NodeUrlMatcher has matched node (' . $ret['node']->getNodeName() . ').', $ret);
            }
            return $ret;
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param string $decodedUrl
     *
     * @return array|bool
     */
    protected function matchNode($decodedUrl)
    {
        if (null !== $this->theme) {
            $tokens = explode('/', $decodedUrl);
            // Remove empty tokens (especially when a trailing slash is present)
            $tokens = array_values(array_filter($tokens));
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
                    return false;
                }

                if (false === $nodeRouteHelper->isViewable()) {
                    return false;
                }

                return [
                    '_controller' => $nodeRouteHelper->getController() . '::' . $nodeRouteHelper->getMethod(),
                    '_locale' => $translation->getLocale(), //pass request locale to init translator
                    'node' => $node,
                    'translation' => $translation,
                    '_route' => null,
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
                    return false;
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
                        return false;
                    }
                    $match = [
                        '_controller' => $nodeRouteHelper->getController() . '::' . $nodeRouteHelper->getMethod(),
                        'node' => $node,
                        'translation' => $translation,
                        '_route' => null,
                    ];

                    if (null !== $translation) {
                        $match['_locale'] = $translation->getLocale(); //pass request locale to init translator
                    }

                    return $match;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Parse Node from UrlAlias.
     *
     * @param array &$tokens
     *
     * @return \RZ\Roadiz\Core\Entities\Node
     */
    protected function parseFromUrlAlias(&$tokens)
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
     * @param array       &$tokens
     * @param Translation $translation
     *
     * @return \RZ\Roadiz\Core\Entities\Node
     */
    protected function parseNode(array &$tokens, Translation $translation)
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
