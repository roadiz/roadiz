<?php
/**
 * Copyright (c) 2016. Rezo Zero
 *
 * DefaultTheme
 *
 * @file AssetsServiceProvider.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\DefaultTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Image formats.
 */
class AssetsServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container['imageFormats'] = function ($c) {
            $array = [];

            $array['headerImage'] = [
                'width' => 1440,
                'progressive' => true,
                'class' => 'img-responsive',
                'autoplay' => true,
                'muted' => true,
                'loop' => true,
                'controls' => false,
            ];

            $array['columnedImage'] = [
                'width' => 720,
                'progressive' => true,
                'class' => 'img-responsive',
            ];

            $array['thumbnail'] = [
                'fit' => '600x338',
                'controls' => true,
                'embed' => true,
                'autoplay' => true,
                'progressive' => true,
                'class' => 'img-responsive',
            ];

            $array['shareImage'] = [
                'fit' => '1200x630',
                'absolute' => true,
                'progressive' => true,
            ];
            return $array;
        };

        return $container;
    }
}
