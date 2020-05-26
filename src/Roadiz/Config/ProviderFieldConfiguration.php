<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ProviderFieldConfiguration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('provider');
        $root = $builder->getRootNode();
        $root->addDefaultsIfNotSet();
        $root->children()
            ->scalarNode('classname')
                ->isRequired()
                ->cannotBeEmpty()
                ->info('Full qualified class name for the Provider class.')
            ->end()
            ->arrayNode('options')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('name')
                            ->cannotBeEmpty()
                            ->isRequired()
                            ->info('Additional option name.')
                        ->end()
                        ->scalarNode('value')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->defaultValue([])
                ->info('Additional options to pass to Provider class.')
            ->end();

        return $builder;
    }
}
