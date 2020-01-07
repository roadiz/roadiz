<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file FactoryServiceProvider.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Handlers\CustomFormFieldHandler;
use RZ\Roadiz\Core\Handlers\CustomFormHandler;
use RZ\Roadiz\Core\Handlers\DocumentHandler;
use RZ\Roadiz\Core\Handlers\FolderHandler;
use RZ\Roadiz\Core\Handlers\FontHandler;
use RZ\Roadiz\Core\Handlers\GroupHandler;
use RZ\Roadiz\Core\Handlers\HandlerFactory;
use RZ\Roadiz\Core\Handlers\NewsletterHandler;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use RZ\Roadiz\Core\Handlers\NodeTypeFieldHandler;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use RZ\Roadiz\Core\Handlers\TagHandler;
use RZ\Roadiz\Core\Handlers\TranslationHandler;
use RZ\Roadiz\Core\Viewers\DocumentViewer;
use RZ\Roadiz\Core\Viewers\TranslationViewer;
use RZ\Roadiz\Core\Viewers\UserViewer;
use RZ\Roadiz\Document\DocumentFinder;
use RZ\Roadiz\Document\DocumentFinderInterface;
use RZ\Roadiz\Document\Renderer\AudioRenderer;
use RZ\Roadiz\Document\Renderer\ChainRenderer;
use RZ\Roadiz\Document\Renderer\EmbedRenderer;
use RZ\Roadiz\Document\Renderer\ImageRenderer;
use RZ\Roadiz\Document\Renderer\InlineSvgRenderer;
use RZ\Roadiz\Document\Renderer\PdfRenderer;
use RZ\Roadiz\Document\Renderer\PictureRenderer;
use RZ\Roadiz\Document\Renderer\RendererInterface;
use RZ\Roadiz\Document\Renderer\SvgRenderer;
use RZ\Roadiz\Document\Renderer\VideoRenderer;
use RZ\Roadiz\Utils\ContactFormManager;
use RZ\Roadiz\Utils\Document\DocumentFactory;
use RZ\Roadiz\Utils\EmailManager;
use RZ\Roadiz\Utils\MediaFinders\EmbedFinderFactory;
use RZ\Roadiz\Utils\Node\NodeFactory;
use RZ\Roadiz\Utils\Tag\TagFactory;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;

class FactoryServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container['emailManager'] = $container->factory(function (Container $c) {
            return new EmailManager(
                $c['requestStack']->getMasterRequest(),
                $c['translator'],
                $c['twig.environment'],
                $c['mailer'],
                $c['settingsBag'],
                $c['document.url_generator']
            );
        });

        $container['contactFormManager'] = $container->factory(function (Container $c) {
            return new ContactFormManager(
                $c['requestStack']->getMasterRequest(),
                $c['formFactory'],
                $c['translator'],
                $c['twig.environment'],
                $c['mailer'],
                $c['settingsBag'],
                $c['document.url_generator']
            );
        });

        $container[NodeFactory::class] = function (Container $c) {
            return new NodeFactory($c);
        };
        $container[TagFactory::class] = function (Container $c) {
            return new TagFactory($c);
        };

        $container['factory.handler'] = function (Container $c) {
            return new HandlerFactory($c);
        };

        $container['node.handler'] = $container->factory(function (Container $c) {
            return new NodeHandler($c['em'], $c['workflow.registry']);
        });
        $container['nodes_sources.handler'] = $container->factory(function (Container $c) {
            return new NodesSourcesHandler($c['em'], $c['settingsBag'], $c['tagApi']);
        });
        $container['node_type.handler'] = $container->factory(function (Container $c) {
            return new NodeTypeHandler($c['em'], $c, $c['kernel']);
        });
        $container['node_type_field.handler'] = $container->factory(function (Container $c) {
            return new NodeTypeFieldHandler($c['em'], $c);
        });
        $container['document.handler'] = $container->factory(function (Container $c) {
            return new DocumentHandler($c['em'], $c['assetPackages']);
        });
        $container['custom_form.handler'] = $container->factory(function (Container $c) {
            return new CustomFormHandler($c['em']);
        });
        $container['custom_form_field.handler'] = $container->factory(function (Container $c) {
            return new CustomFormFieldHandler($c['em'], $c);
        });
        $container['folder.handler'] = $container->factory(function (Container $c) {
            return new FolderHandler($c['em']);
        });
        $container['font.handler'] = $container->factory(function (Container $c) {
            return new FontHandler($c['em']);
        });
        $container['group.handler'] = $container->factory(function (Container $c) {
            return new GroupHandler($c['em']);
        });
        $container['newsletter.handler'] = $container->factory(function (Container $c) {
            return new NewsletterHandler($c['em']);
        });
        $container['tag.handler'] = $container->factory(function (Container $c) {
            return new TagHandler($c['em']);
        });
        $container['translation.handler'] = $container->factory(function (Container $c) {
            return new TranslationHandler($c['em']);
        });

        /*
         * Viewers
         */
        $container['document.renderers'] = function (Container $c) {
            return [
                new ImageRenderer(
                    $c[EmbedFinderFactory::class],
                    $c['twig.environment'],
                    $c['document.url_generator']
                ),
                new PictureRenderer(
                    $c[EmbedFinderFactory::class],
                    $c['twig.environment'],
                    $c['document.url_generator']
                ),
                new VideoRenderer(
                    $c['assetPackages'],
                    $c[DocumentFinderInterface::class],
                    $c['twig.environment'],
                    $c['document.url_generator']
                ),
                new AudioRenderer(
                    $c['assetPackages'],
                    $c[DocumentFinderInterface::class],
                    $c['twig.environment'],
                    $c['document.url_generator']
                ),
                new PdfRenderer(
                    $c['twig.environment'],
                    $c['document.url_generator']
                ),
                new SvgRenderer($c['assetPackages']),
                new InlineSvgRenderer($c['assetPackages']),
                new EmbedRenderer($c[EmbedFinderFactory::class]),
            ];
        };
        $container[RendererInterface::class] = function (Container $c) {
            return new ChainRenderer($c['document.renderers']);
        };

        $container[EmbedFinderFactory::class] = function (Container $c) {
            return new EmbedFinderFactory($c['document.platforms']);
        };

        $container[DocumentFinderInterface::class] = function (Container $c) {
            return new DocumentFinder($c['em']);
        };

        /** @deprecated */
        $container['document.viewer'] = $container->factory(function (Container $c) {
            return new DocumentViewer(
                $c['requestStack'],
                $c['twig.environment'],
                $c['em'],
                $c['urlGenerator'],
                $c['document.url_generator'],
                $c['assetPackages'],
                $c['document.platforms']
            );
        });
        $container['translation.viewer'] = $container->factory(function (Container $c) {
            return new TranslationViewer($c['em'], $c['settingsBag'], $c['router'], $c['kernel']->isPreview());
        });
        $container['user.viewer'] = $container->factory(function (Container $c) {
            return new UserViewer($c['em'], $c['settingsBag'], $c['translator'], $c['emailManager']);
        });

        /*
         * UrlGenerators
         */
        $container['document.url_generator'] = $container->factory(function (Container $c) {
            return new DocumentUrlGenerator($c['requestStack'], $c['assetPackages'], $c['urlGenerator']);
        });

        /*
         * DocumentFactory
         */
        $container['document.factory'] = $container->factory(function (Container $c) {
            return new DocumentFactory($c['em'], $c['dispatcher'], $c['assetPackages'], $c['logger']);
        });

        return $container;
    }
}
