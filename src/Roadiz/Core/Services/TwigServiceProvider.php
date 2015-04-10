<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file TwigServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use RZ\Roadiz\Core\Kernel;
use Asm89\Twig\CacheExtension\CacheProvider\DoctrineCacheAdapter;
use Asm89\Twig\CacheExtension\CacheStrategy\LifetimeCacheStrategy;
use Asm89\Twig\CacheExtension\Extension as CacheExtension;
use Pimple\Container;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use \Parsedown;

/**
 * Register Twig services for dependency injection container.
 */
class TwigServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * @param Pimple\Container $container [description]
     */
    public function register(Container $container)
    {
        $container['twig.cacheFolder'] = function ($c) {
            return ROADIZ_ROOT . '/cache/twig_cache';
        };

        /*
         * Return every paths to search for twig templates.
         */
        $container['twig.loaderFileSystem'] = function ($c) {
            $vendorDir = realpath(ROADIZ_ROOT . '/vendor');

            // le chemin vers TwigBridge pour que Twig puisse localiser
            // le fichier form_div_layout.html.twig
            $vendorTwigBridgeDir =
            $vendorDir . '/symfony/twig-bridge/Symfony/Bridge/Twig';

            return new \Twig_Loader_Filesystem([
                // Default Form extension templates
                $vendorTwigBridgeDir . '/Resources/views/Form',
                ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/views',
            ]);
        };

        /*
         * Main twig environment
         */
        $container['twig.environment'] = function ($c) {

            $twig = new \Twig_Environment($c['twig.loaderFileSystem'], [
                'debug' => $c['config']['devMode'],
                'cache' => $c['twig.cacheFolder'],
            ]);

            $c['twig.formRenderer']->setEnvironment($twig);

            $twig->addExtension(
                new FormExtension(new TwigRenderer(
                    $c['twig.formRenderer'],
                    $c['csrfProvider']
                ))
            );

            $twig->addFilter($c['twig.markdownExtension']);
            $twig->addFilter($c['twig.inlineMarkdownExtension']);
            $twig->addFilter($c['twig.centralTruncateExtension']);
            $twig->addFilter($c['twig.displayExtension']);
            $twig->addFilter($c['twig.urlExtension']);
            $twig->addFilter($c['twig.childrenExtension']);
            $twig->addFilter($c['twig.nextExtension']);
            $twig->addFilter($c['twig.previousExtension']);
            $twig->addFilter($c['twig.lastSibling']);
            $twig->addFilter($c['twig.firstSibling']);
            $twig->addFilter($c['twig.parent']);
            $twig->addFilter($c['twig.parents']);

            /*
             * Extensions
             */
            $twig->addExtension(new TranslationExtension($c['translator']));
            $twig->addExtension(new \Twig_Extensions_Extension_Intl());
            $twig->addExtension($c['twig.routingExtension']);
            $twig->addExtension(new \Twig_Extensions_Extension_Text());

            if (null !== $c['twig.cacheExtension']) {
                $twig->addExtension($c['twig.cacheExtension']);
            }

            if (true === $c['config']['devMode']) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            return $twig;
        };

        /*
         * Twig form renderer extension
         */
        $container['twig.formRenderer'] = function ($c) {

            return new TwigRendererEngine([
                'form_div_layout.html.twig',
            ]);
        };

        /*
         * Twig routing extension
         */
        $container['twig.routingExtension'] = function ($c) {

            return new RoutingExtension($c['urlGenerator']);
        };


        /*
         * Document extensions
         */
        $container['twig.displayExtension'] = function ($c) {
            return new \Twig_SimpleFilter('display', function (Document $document, array $criteria = []) {
                return $document->getViewer()->getDocumentByArray($criteria);
            }, ['is_safe' => ['html']]);
        };
        $container['twig.urlExtension'] = function ($c) {
            return new \Twig_SimpleFilter('url', function (AbstractEntity $mixed, array $criteria = []) {

                if ($mixed instanceof Document) {
                    return $mixed->getViewer()->getDocumentUrlByArray($criteria);
                } elseif ($mixed instanceof NodesSources) {
                    $urlGenerator = new NodesSourcesUrlGenerator(
                        Kernel::getInstance()->getRequest(),
                        $mixed
                    );
                    if (isset($criteria['absolute'])) {
                        return $urlGenerator->getUrl((boolean) $criteria['absolute']);
                    }
                    return $urlGenerator->getUrl(false);
                } elseif ($mixed instanceof Node) {
                    $urlGenerator = new NodesSourcesUrlGenerator(
                        Kernel::getInstance()->getRequest(),
                        $mixed->getNodeSources()->first()
                    );
                    if (isset($criteria['absolute'])) {
                        return $urlGenerator->getUrl((boolean) $criteria['absolute']);
                    }
                    return $urlGenerator->getUrl(false);
                } else {
                    throw new \RuntimeException("Twig “url” filter can be only used with a Document, a NodesSources or a Node", 1);
                }
            });
        };
        /*
         * NodesSources extensions
         */
        $container['twig.childrenExtension'] = function ($c) {
            return new \Twig_SimpleFilter('children', function (NodesSources $ns, array $criteria = null, array $order = null) {
                return $ns->getHandler()->getChildren($criteria, $order, Kernel::getService('securityContext'));
            });
        };
        $container['twig.nextExtension'] = function ($c) {
            return new \Twig_SimpleFilter('next', function (NodesSources $ns, array $criteria = null, array $order = null) {
                return $ns->getHandler()->getNext($criteria, $order, Kernel::getService('securityContext'));
            });
        };
        $container['twig.previousExtension'] = function ($c) {
            return new \Twig_SimpleFilter('previous', function (NodesSources $ns, array $criteria = null, array $order = null) {
                return $ns->getHandler()->getPrevious($criteria, $order, Kernel::getService('securityContext'));
            });
        };
        $container['twig.lastSibling'] = function ($c) {
            return new \Twig_SimpleFilter('lastSibling', function (NodesSources $ns, array $criteria = null, array $order = null) {
                return $ns->getHandler()->getLastSibling($criteria, $order, Kernel::getService('securityContext'));
            });
        };
        $container['twig.firstSibling'] = function ($c) {
            return new \Twig_SimpleFilter('firstSibling', function (NodesSources $ns, array $criteria = null, array $order = null) {
                return $ns->getHandler()->getFirstSibling($criteria, $order, Kernel::getService('securityContext'));
            });
        };
        $container['twig.parent'] = function ($c) {
            return new \Twig_SimpleFilter('parent', function (NodesSources $ns) {
                return $ns->getHandler()->getParent();
            });
        };

        $container['twig.parents'] = function ($c) {
            return new \Twig_SimpleFilter('parents', function (NodesSources $ns, array $criteria = []) {
                return $ns->getHandler()->getParents($criteria, Kernel::getService('securityContext'));
            });
        };

        /*
         * Markdown extension
         */
        $container['twig.markdownExtension'] = function ($c) {

            return new \Twig_SimpleFilter('markdown', function ($object) {
                return Parsedown::instance()->text($object);
            }, ['is_safe' => ['html']]);
        };

        /*
         * InlineMarkdown extension
         */
        $container['twig.inlineMarkdownExtension'] = function ($c) {

            return new \Twig_SimpleFilter('inlineMarkdown', function ($object) {
                return Parsedown::instance()->line($object);
            }, ['is_safe' => ['html']]);
        };

        /*
         * Central Truncate extension
         */
        $container['twig.centralTruncateExtension'] = function ($c) {

            return new \Twig_SimpleFilter(
                'centralTruncate',
                function ($object, $length, $offset = 0, $ellipsis = "[…]") {
                    if (strlen($object) > $length + strlen($ellipsis)) {
                        $str1 = substr($object, 0, floor($length / 2) + floor($offset / 2));
                        $str2 = substr($object, (floor($length / 2) * -1) + floor($offset / 2));

                        return $str1 . $ellipsis . $str2;
                    } else {
                        return $object;
                    }
                }
            );
        };
        /*
         * Twig cache extension
         * see https://github.com/asm89/twig-cache-extension
         */
        $container['twig.cacheExtension'] = function ($c) {

            $resultCacheDriver = $c['em']->getConfiguration()->getResultCacheImpl();
            if ($resultCacheDriver !== null) {
                $cacheProvider = new DoctrineCacheAdapter($resultCacheDriver);
                $cacheStrategy = new LifetimeCacheStrategy($cacheProvider);
                $cacheExtension = new CacheExtension($cacheStrategy);

                return $cacheExtension;
            } else {
                return null;
            }
        };

        return $container;
    }
}
