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
use RZ\Roadiz\Core\Handlers\HandlerFactory;
use RZ\Roadiz\Utils\Document\DocumentFactory;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use RZ\Roadiz\Core\Handlers\CustomFormFieldHandler;
use RZ\Roadiz\Core\Handlers\CustomFormHandler;
use RZ\Roadiz\Core\Handlers\DocumentHandler;
use RZ\Roadiz\Core\Handlers\FolderHandler;
use RZ\Roadiz\Core\Handlers\FontHandler;
use RZ\Roadiz\Core\Handlers\GroupHandler;
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
use RZ\Roadiz\Utils\ContactFormManager;
use RZ\Roadiz\Utils\EmailManager;

class FactoryServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container['emailManager'] = $container->factory(function ($c) {
            return new EmailManager(
                $c['requestStack']->getMasterRequest(),
                $c['translator'],
                $c['twig.environment'],
                $c['mailer'],
                $c['settingsBag'],
                $c['document.url_generator']
            );
        });

        $container['contactFormManager'] = $container->factory(function ($c) {
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

        $container['factory.handler'] = function ($c) {
            return new HandlerFactory($c);
        };

        $container['node.handler'] = $container->factory(function ($c) {
            return new NodeHandler($c['em']);
        });
        $container['nodes_sources.handler'] = $container->factory(function ($c) {
            return new NodesSourcesHandler($c['em'], $c['settingsBag'], $c['tagApi']);
        });
        $container['node_type.handler'] = $container->factory(function ($c) {
            return new NodeTypeHandler($c['em'], $c, $c['kernel']);
        });
        $container['node_type_field.handler'] = $container->factory(function ($c) {
            return new NodeTypeFieldHandler($c['em'], $c);
        });
        $container['document.handler'] = $container->factory(function ($c) {
            return new DocumentHandler($c['em'], $c['assetPackages']);
        });
        $container['custom_form.handler'] = $container->factory(function ($c) {
            return new CustomFormHandler($c['em']);
        });
        $container['custom_form_field.handler'] = $container->factory(function ($c) {
            return new CustomFormFieldHandler($c['em'], $c);
        });
        $container['folder.handler'] = $container->factory(function ($c) {
            return new FolderHandler($c['em']);
        });
        $container['font.handler'] = $container->factory(function ($c) {
            return new FontHandler($c['em']);
        });
        $container['group.handler'] = $container->factory(function ($c) {
            return new GroupHandler($c['em']);
        });
        $container['newsletter.handler'] = $container->factory(function ($c) {
            return new NewsletterHandler($c['em']);
        });
        $container['tag.handler'] = $container->factory(function ($c) {
            return new TagHandler($c['em']);
        });
        $container['translation.handler'] = $container->factory(function ($c) {
            return new TranslationHandler($c['em']);
        });

        /*
         * Viewers
         */

        $container['document.viewer'] = $container->factory(function ($c) {
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
        $container['translation.viewer'] = $container->factory(function ($c) {
            return new TranslationViewer($c['em'], $c['settingsBag'], $c['router'], $c['kernel']->isPreview());
        });
        $container['user.viewer'] = $container->factory(function ($c) {
            return new UserViewer($c['em'], $c['settingsBag'], $c['translator'], $c['emailManager']);
        });

        /*
         * UrlGenerators
         */
        $container['document.url_generator'] = $container->factory(function ($c) {
            return new DocumentUrlGenerator($c['requestStack'], $c['assetPackages'], $c['urlGenerator']);
        });

        /*
         * DocumentFactory
         */
        $container['document.factory'] = $container->factory(function ($c) {
            return new DocumentFactory($c['em'], $c['dispatcher'], $c['assetPackages'], $c['logger']);
        });

        return $container;
    }
}
