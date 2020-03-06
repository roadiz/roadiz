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
 * @file EmbedDocumentsServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Utils\MediaFinders\DailymotionEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\DeezerEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\MixcloudEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\SoundcloudEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\SpotifyEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\TedEmbedFinder;
use RZ\Roadiz\Utils\MediaFinders\TwitchEmbedFinder;
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
                'twitch' => TwitchEmbedFinder::class
            ];
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

        return $container;
    }
}
