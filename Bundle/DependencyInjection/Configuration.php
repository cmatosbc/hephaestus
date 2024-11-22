<?php

namespace Hephaestus\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('hephaestus');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('exception_handling')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_retries')
                            ->defaultValue(3)
                        ->end()
                        ->integerNode('retry_delay')
                            ->defaultValue(1)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('logging')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('channel')
                            ->defaultValue('hephaestus')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
