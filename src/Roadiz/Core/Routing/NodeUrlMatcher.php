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

use RZ\Roadiz\Core\Entities\Translation;

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
        $this->theme = $this->findTheme();
        $this->repository = $this->em->getRepository('RZ\Roadiz\Core\Entities\Node');
        $decodedUrl = rawurldecode($pathinfo);

        /*
         * Try nodes routes
         */
        if (false !== $ret = $this->matchNode($decodedUrl)) {
            return $ret;
        } else {

            if (null !== $this->theme) {
                $ctrl = $this->theme->getClassName();
            } else {
                $ctrl = 'RZ\Roadiz\CMS\Controllers\FrontendController';
            }

            return [
                '_controller' => $ctrl . '::throw404',
                'message' => 'Unable to find any matching route nor matching node. ' .
                'Check your `Resources/routes.yml` file.',
                'node' => null,
                'translation' => null,
            ];
        }
    }

    /**
     * @param string $decodedUrl
     *
     * @return array
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
            $node = $this->parseFromUrlAlias($tokens);

            if ($node !== null) {
                $translation = $node->getNodeSources()->first()->getTranslation();

                if (!$translation->isAvailable()) {
                    return false;
                }

                return [
                    '_controller' => $this->theme->getClassName() . '::indexAction',
                    '_locale' => $translation->getLocale(), //pass request locale to init translator
                    'node' => $node,
                    'translation' => $translation,
                ];
            } else {
                /*
                 * Try with node name
                 */
                $translation = $this->parseTranslation($tokens);

                if ($translation === null) {
                    return false;
                }

                $node = $this->parseNode($tokens, $translation);
                if ($node !== null) {
                    /*
                     * Try with nodeName
                     */
                    $match = [
                        '_controller' => $this->theme->getClassName() . '::indexAction',
                        'node' => $node,
                        'translation' => $translation,
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
     * @return RZ\Roadiz\Core\Entities\Node
     */
    protected function parseFromUrlAlias(&$tokens)
    {
        if (null !== $this->parseUrlAlias($tokens)) {
            return $this->repository->findOneWithUrlAlias($ua);
        }

        return null;
    }

    /**
     * Parse URL searching nodeName.
     *
     * Cannot use securityContext here as firewall
     * has not been hit yet.
     *
     * @param array       &$tokens
     * @param Translation $translation
     *
     * @return RZ\Roadiz\Core\Entities\Node
     */
    protected function parseNode(array &$tokens, Translation $translation)
    {
        if (!empty($tokens[0])) {
            /*
             * If the only url token is for language, return Home page
             */
            if (in_array($tokens[0], Translation::getAvailableLocales()) &&
                count($tokens) == 1) {
                if ($this->theme->getHomeNode() !== null) {
                    $node = $this->theme->getHomeNode();
                    if ($translation !== null) {
                        return $this->repository
                                    ->findWithTranslation(
                                        $node->getId(),
                                        $translation
                                    );
                    } else {
                        return $this->repository->findWithDefaultTranslation($node->getId());
                    }
                }
                return $this->repository->findHomeWithTranslation($translation);
            } else {
                $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);

                if ($identifier !== null &&
                    $identifier != '') {
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
