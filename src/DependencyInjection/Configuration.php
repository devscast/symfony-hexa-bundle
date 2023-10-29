<?php

declare(strict_types=1);

namespace Devscast\Bundle\HexaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 *
 * @author bernard-ng <bernard@devscast.tech>
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('devscast_hexa');
        $root = $tree->getRootNode();

        $this->addApplicationSection($root);

        return $tree;
    }

    public function addApplicationSection(ArrayNodeDefinition|NodeDefinition $root): void
    {
        if ($root instanceof ArrayNodeDefinition) {
            $root
                ->children()
                ->arrayNode('application')
                ->addDefaultsIfNotSet()
                ->children()
                ->scalarNode('domain')->defaultValue('devscast.org')->end()
                ->scalarNode('sender_email')->defaultValue('noreply@devscast.org')->end()
                ->scalarNode('sender_name')->defaultValue('Devscast')->end()
                ->scalarNode('copyrights')->defaultValue('Devscast')->end()
                ->scalarNode('version')->defaultValue('1.0.0')->end()
                ->end()
                ->end()
                ->end();
        }
    }
}
