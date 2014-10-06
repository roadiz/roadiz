<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file TwigServiceProvider.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Services;

use Pimple\Container;

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;

use \Michelf\Markdown;

use RZ\Renzo\Core\Kernel;

/**
 * Register Twig services for dependency injection container.
 */
class TwigServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * @param Pimple\Container $container [description]
     */
    public function register(Container $container)
    {
        $container['twig.cacheFolder'] = function ($c) {
            return RENZO_ROOT.'/cache/twig_cache';
        };

        /*
         * Return every paths to search for twig templates.
         */
        $container['twig.loaderFileSystem'] = function ($c) {
            $vendorDir = realpath(RENZO_ROOT . '/vendor');

            // le chemin vers TwigBridge pour que Twig puisse localiser
            // le fichier form_div_layout.html.twig
            $vendorTwigBridgeDir =
                $vendorDir . '/symfony/twig-bridge/Symfony/Bridge/Twig';

            return new \Twig_Loader_Filesystem(array(
                RENZO_ROOT.'/src/Renzo/Core/Resources/views',
                // Custom Form extension templates
                RENZO_ROOT.'/src/Renzo/CMS/Resources/views/forms',
                 // Default Form extension templates
                $vendorTwigBridgeDir.'/Resources/views/Form'
            ));
        };

        /*
         * Main twig environment
         */
        $container['twig.environment'] = function ($c) {

            $devMode = (isset($c['config']['devMode']) && $c['config']['devMode'] == true) ?
                        true :
                        false;

            $twig = new \Twig_Environment($c['twig.loaderFileSystem'], array(
                'debug' => $devMode,
                'cache' => $c['twig.cacheFolder'],
            ));

            $c['twig.formRenderer']->setEnvironment($twig);

            $twig->addExtension(
                new FormExtension(new TwigRenderer(
                    $c['twig.formRenderer'],
                    $c['csrfProvider']
                ))
            );

            $twig->addFilter($c['twig.markdownExtension']);

            /*
             * Extensions
             */
            $twig->addExtension(new \Twig_Extensions_Extension_Intl());
            $twig->addExtension($c['twig.routingExtension']);

            /*
             * ============================================================================
             * Trucate
             * ============================================================================
             */
            $twig->addExtension(new \Twig_Extensions_Extension_Text());
            /*
             * ============================================================================
             * Dump
             * ============================================================================
             */
            if ($devMode) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            return $twig;
        };

        /*
         * Twig form renderer extension
         */
        $container['twig.formRenderer'] = function ($c) {
            $defaultFormTheme = 'form_div_layout.html.twig';

            return new TwigRendererEngine(array(
                $defaultFormTheme,
                'fields.html.twig'
            ));
        };

        /*
         * Twig routing extension
         */
        $container['twig.routingExtension'] = function ($c) {

            return new RoutingExtension($c['urlGenerator']);
        };

        $container['twig.markdownExtension'] = function ($c) {

            return new \Twig_SimpleFilter('markdown', function ($object) {
                return Markdown::defaultTransform($object);
            }, array('is_safe' => array('html')));
        };
    }
}
