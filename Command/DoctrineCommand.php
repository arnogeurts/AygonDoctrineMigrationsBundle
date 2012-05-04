<?php

namespace Aygon\DoctrineMigrationsBundle\Command;

use Doctrine\DBAL\Migrations\Tools\Console\Command\DoctrineCommand as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Connection;
use Aygon\DoctrineMigrationsBundle\Migrations\Configuration\NamedConfiguration;
use Aygon\DoctrineMigrationsBundle\Migrations\Configuration\CollectionConfiguration;

abstract class DoctrineCommand extends BaseCommand
{
    /**
     * Reconfigure given command
     * 
     * @param BaseCommand $command 
     * @return void
     */
    public static function reconfigureCommand(BaseCommand $command)
    {
        self::addNameArgumentToCommand($command);
        self::removeConfigurationOptionsFromCommand($command);
    }
    
    /**
     * Add name argument to command
     * 
     * @param BaseCommand $command 
     * @return void
     */
    public static function addNameArgumentToCommand(BaseCommand $command)
    {
        // set bundle name as first argument
        $arguments = $command->getDefinition()->getArguments();
        array_unshift($arguments, new InputArgument('name', InputArgument::OPTIONAL, 'The bundle name'));
        $command->getDefinition()->setArguments($arguments);
    }
    
    /**
     * Remove configuration options from the given command
     * 
     * @param BaseCommand $command 
     * @return void
     */
    public static function removeConfigurationOptionsFromCommand(BaseCommand $command)
    {
        $remove = array('configuration', 'db-configuration');
        
        // remove configuration options
        $options = $command->getDefinition()->getOptions();
        $newOptions = array();

        foreach($options as $option) {
            if(!in_array($option->getName(), $remove)) {
                $newOptions[] = $option;
            }
        }
        $command->getDefinition()->setOptions($newOptions);
    }
	
    /**
     * Pre-execute a given command, i.e. set migrations configuration
     * 
     * @param BaseCommand $command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void 
     */
    public static function preExecuteCommand(BaseCommand $command, InputInterface $input, OutputInterface $output, $verifyDir = false)
    {
        $container = $command->getApplication()->getKernel()->getContainer();
        $conn = $command->getApplication()->getHelperSet()->get('db')->getConnection();
        
        $outputWriter = new OutputWriter(function($message) use ($output) {
            return $output->writeln($message);
        });

        // create migrations configuration collection
        $configuration = new CollectionConfiguration($conn, $outputWriter);
        
        // add bundle configurations and application configuration to the collection
        $bundles = $command->getApplication()->getKernel()->getBundles();
        foreach($bundles as $bundle) {
            $configuration->addConfiguration(self::createBundleConfiguration($bundle, $container, $conn, $outputWriter));
        }
        $configuration->addConfiguration(self::createApplicationConfiguration($conn, $container, $outputWriter));
        
        // set main configuration in the collection
        if( ! $input->getDefinition()->hasArgument('name') || $input->getArgument('name') === null) {
            $main = $container->getParameter('aygon_doctrine_migrations.application_name');
        } else {
            $main = $input->getArgument('name');
        }
        $configuration->setMainConfiguration($main);
        
        if($verifyDir && ! file_exists($configuration->getMigrationsDirectory())) {
            mkdir($configuration->getMigrationsDirectory(), 0777, true);
        }
        
        // set configuration
        $this->setMigrationConfiguration($configuration);
    }

    /**
     * Create migrations configuration for given bundle
     * 
     * @param BundleInterface $bundle
     * @param Container $container
     * @param Connection $conn
     * @param OutputWriter $outputWriter
     * @return NamedConfiguration 
     */
    public static function createBundleConfiguration(BundleInterface $bundle, ContainerInterface $container, Connection $conn, OutputWriter $outputWriter)
    {
        $configuration = new NamedConfiguration($bundle->getName(), $conn, $outputWriter);
        $configuration->setContainer($container);
        $configuration->setMigrationsNamespace($bundle->getNamespace() . '\\' . $container->getParameter('aygon_doctrine_migrations.bundle_namespace'));        
        $directory = str_replace('\\', DIRECTORY_SEPARATOR, $container->getParameter('aygon_doctrine_migrations.bundle_namespace'));
        $configuration->setMigrationsDirectory($bundle->getPath() . DIRECTORY_SEPERATOR . $directory);
        $configuration->registerMigrationsFromDirectory($configuration->getMigrationsDirectory());
        $configuration->setMigrationsTableName($container->getParameter('aygon_doctrine_migrations.table_name'));
        return $configuration;
    }
    
    /**
     * Create migrations configuration for the application
     * 
     * @param Container $container
     * @param Connection $conn
     * @param OutputWriter $outputWriter
     * @return NamedConfiguration 
     */
    public static function createApplicationConfiguration(ContainerInterface $container, Connection $conn, OutputWriter $outputWriter)
    {
        $configuration = new NamedConfiguration($container->getParameter('aygon_doctrine_migrations.application_name'), $conn, $outputWriter);
        $configuration->setContainer($container);
        $configuration->setMigrationsNamespace($container->getParameter('aygon_doctrine_migrations.application_namespace'));
        $configuration->setMigrationsDirectory($container->getParameter('aygon_doctrine_migrations.application_directory'));
        $configuration->registerMigrationsFromDirectory($configuration->getMigrationsDirectory());
        $configuration->setMigrationsTableName($container->getParameter('aygon_doctrine_migrations.table_name'));
        return $configuration;
    }
}