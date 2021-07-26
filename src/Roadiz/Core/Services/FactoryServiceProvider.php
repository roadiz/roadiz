<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Authorization\Chroot\NodeChrootResolver;
use RZ\Roadiz\Core\Handlers\CustomFormFieldHandler;
use RZ\Roadiz\Core\Handlers\CustomFormHandler;
use RZ\Roadiz\Core\Handlers\DocumentHandler;
use RZ\Roadiz\Core\Handlers\FolderHandler;
use RZ\Roadiz\Core\Handlers\FontHandler;
use RZ\Roadiz\Core\Handlers\GroupHandler;
use RZ\Roadiz\Core\Handlers\HandlerFactory;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use RZ\Roadiz\Core\Handlers\NodeTypeFieldHandler;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use RZ\Roadiz\Core\Handlers\TagHandler;
use RZ\Roadiz\Core\Handlers\TranslationHandler;
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
use RZ\Roadiz\Document\Renderer\ThumbnailRenderer;
use RZ\Roadiz\Document\Renderer\VideoRenderer;
use RZ\Roadiz\EntityGenerator\EntityGeneratorFactory;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Utils\ContactFormManager;
use RZ\Roadiz\Utils\Document\DocumentFactory;
use RZ\Roadiz\Utils\Document\PrivateDocumentFactory;
use RZ\Roadiz\Utils\EmailManager;
use RZ\Roadiz\Utils\MediaFinders\EmbedFinderFactory;
use RZ\Roadiz\Utils\Node\NodeFactory;
use RZ\Roadiz\Utils\Node\NodeNamePolicyInterface;
use RZ\Roadiz\Utils\Tag\TagFactory;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;

class FactoryServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple)
    {
        $pimple['emailManager'] = $pimple->factory(function (Container $c) {
            return new EmailManager(
                $c['requestStack']->getMasterRequest(),
                $c['translator'],
                $c['twig.environment'],
                $c['mailer'],
                $c['settingsBag'],
                $c['document.url_generator']
            );
        });

        $pimple['contactFormManager'] = $pimple->factory(function (Container $c) {
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

        $pimple[NodeFactory::class] = function (Container $c) {
            return new NodeFactory($c[ManagerRegistry::class], $c[NodeNamePolicyInterface::class]);
        };
        $pimple[TagFactory::class] = function (Container $c) {
            return new TagFactory($c[ManagerRegistry::class]);
        };
        $pimple['factory.handler'] = function (Container $c) {
            return new HandlerFactory($c);
        };

        $pimple['node.handler'] = $pimple->factory(function (Container $c) {
            return new NodeHandler(
                $c['em'],
                $c['workflow.registry'],
                $c[NodeChrootResolver::class],
                $c[NodeNamePolicyInterface::class]
            );
        });
        $pimple['nodes_sources.handler'] = $pimple->factory(function (Container $c) {
            return new NodesSourcesHandler($c['em'], $c['settingsBag'], $c['tagApi']);
        });
        $pimple['node_type.handler'] = $pimple->factory(function (Container $c) {
            return new NodeTypeHandler(
                $c['em'],
                $c['kernel'],
                $c[EntityGeneratorFactory::class],
                $c['factory.handler'],
                $c[ManagerRegistry::class]
            );
        });
        $pimple['node_type_field.handler'] = $pimple->factory(function (Container $c) {
            return new NodeTypeFieldHandler($c['em'], $c);
        });
        $pimple['document.handler'] = $pimple->factory(function (Container $c) {
            return new DocumentHandler($c['em'], $c['assetPackages']);
        });
        $pimple['custom_form.handler'] = $pimple->factory(function (Container $c) {
            return new CustomFormHandler($c['em']);
        });
        $pimple['custom_form_field.handler'] = $pimple->factory(function (Container $c) {
            return new CustomFormFieldHandler($c['em'], $c['custom_form.handler']);
        });
        $pimple['folder.handler'] = $pimple->factory(function (Container $c) {
            return new FolderHandler($c['em']);
        });
        $pimple['font.handler'] = $pimple->factory(function (Container $c) {
            return new FontHandler($c['em']);
        });
        $pimple['group.handler'] = $pimple->factory(function (Container $c) {
            return new GroupHandler($c['em']);
        });
        $pimple['tag.handler'] = $pimple->factory(function (Container $c) {
            return new TagHandler($c['em']);
        });
        $pimple['translation.handler'] = $pimple->factory(function (Container $c) {
            return new TranslationHandler($c['em']);
        });

        /*
         * Viewers
         */
        $pimple['document.renderers'] = function (Container $c) {
            return [
                new ImageRenderer(
                    $c[EmbedFinderFactory::class],
                    $c['twig.environment_class'],
                    $c['document.url_generator']
                ),
                new PictureRenderer(
                    $c[EmbedFinderFactory::class],
                    $c['twig.environment_class'],
                    $c['document.url_generator']
                ),
                new VideoRenderer(
                    $c['assetPackages'],
                    $c[DocumentFinderInterface::class],
                    $c['twig.environment_class'],
                    $c['document.url_generator']
                ),
                new AudioRenderer(
                    $c['assetPackages'],
                    $c[DocumentFinderInterface::class],
                    $c['twig.environment_class'],
                    $c['document.url_generator']
                ),
                new PdfRenderer(
                    $c['twig.environment_class'],
                    $c['document.url_generator']
                ),
                new SvgRenderer($c['assetPackages']),
                new InlineSvgRenderer($c['assetPackages']),
                new EmbedRenderer($c[EmbedFinderFactory::class]),
            ];
        };
        $pimple[RendererInterface::class] = function (Container $c) {
            $chainRenderer = new ChainRenderer($c['document.renderers']);
            $chainRenderer->addRenderer(new ThumbnailRenderer($chainRenderer));
            return $chainRenderer;
        };

        $pimple[DocumentFinderInterface::class] = function (Container $c) {
            return new DocumentFinder($c[ManagerRegistry::class]);
        };

        $pimple['translation.viewer'] = $pimple->factory(function (Container $c) {
            return new TranslationViewer(
                $c[ManagerRegistry::class],
                $c['settingsBag'],
                $c['router'],
                $c[PreviewResolverInterface::class]
            );
        });

        $pimple['user.viewer'] = $pimple->factory(function (Container $c) {
            return new UserViewer(
                $c['settingsBag'],
                $c['translator'],
                $c['emailManager'],
                $c['logger']
            );
        });

        /*
         * UrlGenerators
         */
        $pimple['document.url_generator'] = $pimple->factory(function (Container $c) {
            $cacheProvider = $c[CacheProvider::class];
            if ($cacheProvider instanceof ArrayCache) {
                $cacheProvider = null;
            }
            return new DocumentUrlGenerator($c['assetPackages'], $c['staticRouter'], $cacheProvider);
        });

        /*
         * DocumentFactory
         */
        $pimple['document.factory'] = $pimple->factory(function (Container $c) {
            return new DocumentFactory($c[ManagerRegistry::class], $c['dispatcher'], $c['assetPackages'], $c['logger']);
        });
        $pimple[DocumentFactory::class] = $pimple->factory(function (Container $c) {
            return $c['document.factory'];
        });
        $pimple[PrivateDocumentFactory::class] = $pimple->factory(function (Container $c) {
            return new PrivateDocumentFactory($c[ManagerRegistry::class], $c['dispatcher'], $c['assetPackages'], $c['logger']);
        });

        return $pimple;
    }
}
