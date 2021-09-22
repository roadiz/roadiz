<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\CMS\Utils\NodeApi;
use RZ\Roadiz\CMS\Utils\NodeSourceApi;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Bags\NodeTypes;

class NodeServiceProvider implements ServiceProviderInterface
{
    protected ?TranslationInterface $translation;
    protected Container $coreServices;
    protected NodeApi $nodeApi;
    protected NodeSourceApi $nodeSourceApi;
    protected NodeTypes $nodeTypesBag;

    public function __construct(
        NodeApi $nodeApi,
        NodeSourceApi $nodeSourceApi,
        NodeTypes $nodeTypesBag,
        TranslationInterface $translation = null
    ) {
        $this->translation = $translation;
        $this->nodeApi = $nodeApi;
        $this->nodeSourceApi = $nodeSourceApi;
        $this->nodeTypesBag = $nodeTypesBag;
    }

    /**
     * @param Container $pimple
     * @return Container
     */
    public function register(Container $pimple)
    {
        $pimple['nodeMenu'] = function ($c) {
            return $this->nodeApi->getOneBy(
                [
                    'nodeType' => $this->nodeTypesBag->get('Neutral'),
                    'nodeName' => 'main-menu',
                ]
            );
        };

        /*
         * Register Main navigation
         * This is nodeSources !
         */
        $pimple['navigation'] = function ($c) {
            if ($c['nodeMenu'] !== null) {
                return $this->nodeSourceApi->getBy(
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

        return $pimple;
    }
}
