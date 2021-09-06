<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;

/**
 * @package Themes\DefaultTheme\Services
 */
class NodeServiceProvider implements ServiceProviderInterface
{
    protected ?TranslationInterface $translation;
    protected Container $coreServices;

    /**
     * @param Container $coreServices
     * @param TranslationInterface|null $translation
     */
    public function __construct(Container $coreServices, TranslationInterface $translation = null)
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
                        'nodeType' => $this->coreServices['nodeTypesBag']->get('Neutral'),
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
