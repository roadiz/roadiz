<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core;

use RZ\Roadiz\Core\Kernel;

/**
 * Customize Roadiz kernel with your own project settings.
 */
class SourceKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function register(\Pimple\Container $container)
    {
        parent::register($container);

        /*
         * Enable Rozier backoffice
         */
        if (class_exists('\\Themes\\Rozier\\Services\\RozierServiceProvider')) {
            $container->register(new \Themes\Rozier\Services\RozierServiceProvider());
        }
        /*
         * Add your own service providers.
         */
    }
}
