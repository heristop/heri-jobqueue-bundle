<?php

namespace Heri\JobQueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('heri_job_queue');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                ->end()
                ->integerNode('max_messages')
                    ->defaultValue(1)
                    ->min(1)
                ->end()
                ->arrayNode('queues')
                    ->prototype('scalar')
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
