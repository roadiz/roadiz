<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class CollectionFieldConfiguration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('collection');
        $root = $builder->getRootNode();
        $root->children()
            ->scalarNode('entry_type')
                ->isRequired()
                ->cannotBeEmpty()
                ->info('Full qualified class name for the AbstractType class.')
            ->end();

        return $builder;
    }
}
