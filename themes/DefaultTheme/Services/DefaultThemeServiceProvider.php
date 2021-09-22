<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesIndexingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Themes\DefaultTheme\Event\LinkPathSubscriber;
use Themes\DefaultTheme\Serialization\DocumentUriSubscriber;
use Themes\DefaultTheme\Twig\ImageFormatsExtension;

class DefaultThemeServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple)
    {
        $pimple->extend('twig.extensions', function (ArrayCollection $extensions) {
            $extensions->add(new ImageFormatsExtension());
            return $extensions;
        });

        $pimple->extend('serializer.subscribers', function ($subscribers, Container $c) {
            $subscribers[] = new DocumentUriSubscriber($c);
            return $subscribers;
        });

        $pimple->extend('backoffice.entries', function (array $entries, Container $c) {
            /*
             * Add a test entry in your Backoffice
             * Remove this in your theme if you don’t
             * want to extend Back-office
             */
            $entries['test'] = [
                'name' => 'test',
                'path' => $c['urlGenerator']->generate('adminTestPage'),
                'icon' => 'uk-icon-cube',
                'roles' => null,
                'subentries' => null,
            ];

            return $entries;
        });

        /*
         * Example:
         * Alter Solr indexing with custom data.
         */
        $pimple->extend('dispatcher', function (EventDispatcherInterface $dispatcher) {
            $dispatcher->addSubscriber(new LinkPathSubscriber());
            $dispatcher->addListener(
                NodesSourcesIndexingEvent::class,
                function (NodesSourcesIndexingEvent $event) {
                    $assoc = $event->getAssociations();
                    $assoc['defaulttheme_txt'] = 'This is injected by Default theme during indexing.';
                    $event->setAssociations($assoc);
                }
            );
            return $dispatcher;
        });
    }
}
