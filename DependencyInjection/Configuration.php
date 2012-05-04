<?php

namespace Aygon\DoctrineMigrationsBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('aygon_doctrine_migrations');

        $rootNode
            ->children()
                ->scalarNode('application_name')->defaultValue('Application')->cannotBeEmpty()->end()
                ->scalarNode('application_directory')->defaultValue('%kernel.root_dir%/Migrations')->cannotBeEmpty()->end()
                ->scalarNode('application_namespace')->defaultValue('Application\\Migrations')->cannotBeEmpty()->end()
                ->scalarNode('bundle_namespace')->defaultValue('Migrations')->cannotBeEmpty()->end()
                ->scalarNode('table_name')->defaultValue('migration_versions')->cannotBeEmpty()->end()
            ->end()
        ;
        
        return $treeBuilder;
    }
}
