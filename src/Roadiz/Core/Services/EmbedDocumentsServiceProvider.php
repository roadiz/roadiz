<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Utils\MediaFinders\DailymotionEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\DeezerEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\EmbedFinderFactory;
use RZ\Roadiz\Utils\MediaFinders\MixcloudEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\PodcastFinder;
use RZ\Roadiz\Utils\MediaFinders\RandomImageFinder;
use RZ\Roadiz\Utils\MediaFinders\SoundcloudEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\SpotifyEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\TedEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\TwitchEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\UnsplashPictureFinder;
use RZ\Roadiz\Utils\MediaFinders\VimeoEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\YoutubeEmbedFinder;

/**
 * Register Embed documents services for dependency injection container.
 */
class EmbedDocumentsServiceProvider implements ServiceProviderInterface
{
    /**
     * Initialize Doctrine entity manager in DI container.
     *
     * This method can be called from InstallApp after updating
     * doctrine configuration.
     *
     * @param Container $container [description]
     * @return Container
     */
    public function register(Container $container)
    {
        $container['document.platforms'] = function () {
            return [
                'youtube' => YoutubeEmbedFinder::class,
                'vimeo' => VimeoEmbedFinder::class,
                'deezer' => DeezerEmbedFinder::class,
                'dailymotion' => DailymotionEmbedFinder::class,
                'soundcloud' => SoundcloudEmbedFinder::class,
                'mixcloud' => MixcloudEmbedFinder::class,
                'spotify' => SpotifyEmbedFinder::class,
                'ted' => TedEmbedFinder::class,
                'podcast' => PodcastFinder::class,
                'twitch' => TwitchEmbedFinder::class
            ];
        };

        $container[EmbedFinderFactory::class] = function (Container $c) {
            return new EmbedFinderFactory($c['document.platforms']);
        };

        $container['embed_finder.youtube'] = $container->factory(function (Container $c) {
            $finder = new YoutubeEmbedFinder('', false);
            $finder->setKey($c['settingsBag']->get('google_server_id'));
            return $finder;
        });

        $container['embed_finder.vimeo'] = $container->factory(function () {
            return new VimeoEmbedFinder('', false);
        });

        $container['embed_finder.deezer'] = $container->factory(function () {
            return new DeezerEmbedFinder('', false);
        });

        $container['embed_finder.mixcloud'] = $container->factory(function () {
            return new MixcloudEmbedFinder('', false);
        });

        $container['embed_finder.spotify'] = $container->factory(function () {
            return new SpotifyEmbedFinder('', false);
        });

        $container['embed_finder.ted'] = $container->factory(function () {
            return new TedEmbedFinder('', false);
        });

        $container['embed_finder.twitch'] = $container->factory(function () {
            return new TwitchEmbedFinder('', false);
        });

        $container['embed_finder.dailymotion'] = $container->factory(function () {
            return new DailymotionEmbedFinder('', false);
        });

        $container['embed_finder.soundcloud'] = $container->factory(function (Container $c) {
            $finder = new SoundcloudEmbedFinder('', false);
            $finder->setKey($c['settingsBag']->get('soundcloud_client_id'));
            return $finder;
        });

        $container['embed_finder.unsplash'] = $container->factory(function (Container $c) {
            return new UnsplashPictureFinder($c['settingsBag']->get('unsplash_client_id') ?? '');
        });

        $container[RandomImageFinder::class] = $container->factory(function (Container $c) {
            return $c['embed_finder.unsplash'];
        });

        return $container;
    }
}
