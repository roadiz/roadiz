<?php

namespace RZ\Renzo\Core\Services;

use Pimple\Container;

use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

use RZ\Renzo\Core\Events\DataInheritanceEvent;
use RZ\Renzo\Core\Kernel;

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
        $container['document.platforms'] = function ($c) {
            return array(
                'youtube' => '\RZ\Renzo\Core\Utils\YoutubeEmbedFinder',
                'vimeo' => '\RZ\Renzo\Core\Utils\VimeoEmbedFinder',
                'dailymotion' => '\RZ\Renzo\Core\Utils\DailymotionEmbedFinder',
                'soundcloud' => '\RZ\Renzo\Core\Utils\SoundcloudEmbedFinder'
            );
        };
    }
}