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

/**
 * Register Embed documents services for dependency injection container.
 */
class EmbedDocumentsServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Initialize Doctrine entity manager in DI container.
     *
     * This method can be called from InstallApp after updating
     * doctrine configuration.
     *
     * @param Pimple\Container $container [description]
     */
    public function register(Container $container)
    {
        $container['document.platforms'] = function () {
            return [
                'youtube' =>     '\RZ\Roadiz\Utils\MediaFinders\YoutubeEmbedFinder',
                'vimeo' =>       '\RZ\Roadiz\Utils\MediaFinders\VimeoEmbedFinder',
                'dailymotion' => '\RZ\Roadiz\Utils\MediaFinders\DailymotionEmbedFinder',
                'soundcloud' =>  '\RZ\Roadiz\Utils\MediaFinders\SoundcloudEmbedFinder'
            ];
        };

        return $container;
    }
}
