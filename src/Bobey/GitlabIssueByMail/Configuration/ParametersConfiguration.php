<?php

namespace Bobey\GitlabIssueByMail\Configuration;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ParametersConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('parameters');

        $rootNode
            ->children()

                ->arrayNode('mail')
                    ->children()
                        ->scalarNode('server')->end()
                        ->scalarNode('port')->end()
                        ->enumNode('type')
                            ->values(['pop3', 'imap'])
                        ->end()
                        ->scalarNode('username')->end()
                        ->scalarNode('password')->end()
                    ->end()
                ->end()

                ->arrayNode('gitlab')
                    ->children()
                        ->scalarNode('host')->end()
                        ->scalarNode('projectId')->end()
                        ->scalarNode('token')->end()
                    ->end()
                ->end()

            ->end()
        ;

        return $treeBuilder;
    }
}
