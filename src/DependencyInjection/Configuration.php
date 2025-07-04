<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('terminal42_url_rewrite');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->booleanNode('backend_management')
                    ->info('Enable the rewrites management in Contao backend.')
                    ->defaultTrue()
                ->end()
                ->arrayNode('entries')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('request')
                                ->children()
                                    ->scalarNode('path')
                                        ->info('The request path to match.')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->arrayNode('hosts')
                                        ->info('An array of hosts to match.')
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->arrayNode('requirements')
                                        ->info('Additional requirements to match.')
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->scalarNode('condition')
                                        ->info('Request condition in Symfony\'s Expression Language to match.')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('response')
                                ->children()
                                    ->integerNode('code')
                                        ->info('The response code.')
                                        ->defaultValue(301)
                                        ->validate()
                                        ->ifNotInArray(RewriteConfigInterface::VALID_RESPONSE_CODES)
                                            ->thenInvalid('Invalid response code %s.')
                                        ->end()
                                    ->end()
                                    ->arrayNode('conditionalUris')
                                        ->useAttributeAsKey('name')
                                        ->normalizeKeys(false)
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()
                                    ->scalarNode('uri')
                                        ->info('The response redirect URI. Irrelevant if response code is set to 410.')
                                    ->end()
                                    ->booleanNode('keepQueryParams')
                                        ->info('Whether or not to keep the request\'s query parameters.')
                                        ->defaultFalse()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
