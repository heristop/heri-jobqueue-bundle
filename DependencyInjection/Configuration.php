<?php

namespace Heri\Bundle\JobQueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('heri_job_queue');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()

                ->scalarNode('adapter')
                    ->defaultValue('doctrine')
                    ->validate()
                    ->ifNotInArray(['doctrine', 'amqp'])
                        ->thenInvalid('Invalid adapter "%s"')
                    ->end()
                ->end()
                ->booleanNode('enabled')
                    ->defaultTrue()
                ->end()
                ->integerNode('max_messages')
                    ->defaultValue(1)
                    ->min(1)
                ->end()
                ->integerNode('process_timeout')
                    ->defaultNull()
                    ->min(1)
                ->end()
                ->arrayNode('queues')
                    ->prototype('scalar')
                    ->end()
                ->end()

                ->arrayNode('amqp_connection')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')->defaultValue('localhost')->end()
                        ->scalarNode('port')->defaultValue('5672')->end()
                        ->scalarNode('user')->defaultValue('guest')->end()
                        ->scalarNode('password')->defaultValue('guest')->end()
                    ->end()
                ->end()

            ->end()
        ;

        return $treeBuilder;
    }
}
