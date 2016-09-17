<?php
/**
 * Copyright (c) 2016. Rezo Zero
 *
 * DefaultTheme
 *
 * @file NodeTypeServiceProvider.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\DefaultTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Utils\NodeTypeApi;

class NodeTypeServiceProvider implements ServiceProviderInterface
{
    /**
     * @var NodeTypeApi
     */
    protected $api;
    /**
     * NodeTypeServiceProvider constructor.
     * @param NodeTypeApi $api
     */
    public function __construct(NodeTypeApi $api)
    {
        $this->api = $api;
    }

    /**
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container['typePage'] = function () {
            return $this->api->getOneBy(['name' => 'Page']);
        };

        return $container;
    }
}
