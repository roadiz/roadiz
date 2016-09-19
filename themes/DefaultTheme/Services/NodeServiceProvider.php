<?php
/**
 * Copyright (c) 2016. Rezo Zero
 *
 * DefaultTheme
 *
 * @file NodeServiceProvider.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\DefaultTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * Class NodeServiceProvider
 * @package Themes\DefaultTheme\Services
 */
class NodeServiceProvider implements ServiceProviderInterface
{
    protected $translation;
    protected $coreServices;

    /**
     * NodeServiceProvider constructor.
     * @param Container $coreServices
     * @param Translation|null $translation
     */
    public function __construct(Container $coreServices, Translation $translation = null)
    {
        $this->coreServices = $coreServices;
        $this->translation = $translation;
    }

    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container['nodeMenu'] = function ($c) {
            return $this->coreServices['nodeApi']
                ->getOneBy(
                    [
                        'nodeName' => 'main-menu',
                    ]
                );
        };

        /*
         * Register Main navigation
         * This is nodeSources !
         */
        $container['navigation'] = function ($c) {
            if ($c['nodeMenu'] !== null) {
                return $this->coreServices['nodeSourceApi']
                    ->getBy(
                        [
                            'node.parent' => $c['nodeMenu'],
                            'node.visible' => true,
                            'translation' => $this->translation,
                        ],
                        ['node.position' => 'ASC']
                    );
            }

            return null;
        };

        return $container;
    }
}
