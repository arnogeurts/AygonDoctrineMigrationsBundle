<?php

namespace Aygon\DoctrineMigrationsBundle\Migrations\Configuration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

/**
 * @author      Arno Geurts
 */
class CollectionConfiguration extends Configuration
{
    /**
     * Main configuration
     * @type string 
     */
    private $mainConfiguration;
    
    /**
     * All subconfigurations
     * @var array
     */
    private $configurations = array();
    
    /**
     * Add specific configuration
     * 
     * @param Configuration $configuration 
     * @return void
     */
    public function addConfiguration(Configuration $configuration) 
    {
        $this->configurations[$configuration->getName()] = $configuration;
    }
    
    /**
     * Set all configurations
     * 
     * @param array $configurations 
     * @return void
     */
    public function setConfigurations(array $configurations)
    {
        $this->mainConfiguration = null;
        $this->configurations = array();
        
        foreach ($configurations as $config) {
            $this->addConfiguration($configuration);
        }
    }
    
    /**
     * Get a specific configuration from the collection
     *
     * @param string $name
     * @return Configuration
     */    
    public function getConfiguration($name) 
    {
        if (!array_key_exists($name, $this->configurations)) {
            throw new MigrationException(sprintf('No configuration found named %s.', $name));
        }
        return $this->configurations[$name];
    }
    
    /**
     * Get all configurations from the collection
     *
     * @return array
     */
    public function getConfigurations() 
    {
        return $this->configurations;
    }
    
    /**
     * Set main configuration
     *  
     * @param string $name
     * @return void
     */
    public function setMainConfiguration($name) 
    {
        $this->mainConfiguration = $name;
    }
    
    /**
     * Get main configuration
     * 
     * @return Configuration 
     */
    public function getMainConfiguration()
    {
        if ($this->mainConfiguration === null) {
            throw new MigrationException('No main configuration defined in collection configuration.');
        }
        return $this->getConfiguration($this->mainConfiguration);
    }

    /**
     * Validation that this instance has all the required properties configured
     *
     * @return void
     * @throws MigrationException
     */
    public function validate()
    {
        // check if a valid main configuration is defined
        $this->getMainConfiguration();
        
        // validate all configurations
        foreach($this->getConfigurations() as $config) {
            $config->validate();
        }
    }

    /**
     * Returns a timestamp version as a formatted date
     *
     * @param string $version 
     * @return string $formattedVersion The formatted version
     */
    public function formatVersion($version)
    {
        foreach ($this->getConfigurations() as $config) {
            if ($config->hasVersion($version)) {
                return $config->formatVersion($version);
            } 
        }
        return $this->getMainConfiguration()->formatVersion($version);
    }

    /**
     * Set the migration table name
     *
     * @param string $tableName The migration table name
     */
    public function setMigrationsTableName($tableName)
    {
        foreach($this->getConfigurations() as $config) {
            $config->setMigrationsTableName($tableName);
        }
    }

    /**
     * Returns the migration table name
     *
     * @return string $migrationsTableName The migration table name
     */
    public function getMigrationsTableName()
    {
        return $this->getMainConfiguration()->getMigrationsTableName();
    }

    /**
     * Set the new migrations directory where new migration classes are generated
     *
     * @param string $migrationsDirectory The new migrations directory 
     */
    public function setMigrationsDirectory($migrationsDirectory)
    {
        return $this->getMainConfiguration()->setMigrationsDirectory($migrationsDirectory);
    }

    /**
     * Returns the new migrations directory where new migration classes are generated
     *
     * @return string $migrationsDirectory The new migrations directory
     */
    public function getMigrationsDirectory()
    {
        return $this->getMainConfiguration()->getMigrationsDirectory();
    }

    /**
     * Set the migrations namespace
     *
     * @param string $migrationsNamespace The migrations namespace
     */
    public function setMigrationsNamespace($migrationsNamespace)
    {
        return $this->getMainConfiguration()->setMigrationsNamespace($migrationsNamespace);
    }

    /**
     * Returns the migrations namespace
     *
     * @return string $migrationsNamespace The migrations namespace
     */
    public function getMigrationsNamespace()
    {
        return $this->getMainConfiguration()->getMigrationsNamespace();
    }

    /**
     * Register migrations from a given directory. Recursively finds all files
     * with the pattern VersionYYYYMMDDHHMMSS.php as the filename and registers
     * them as migrations.
     *
     * @param string $path  The root directory to where some migration classes live.
     * @return $migrations  The array of migrations registered.
     */
    public function registerMigrationsFromDirectory($path)
    {
        return $this->getMainConfiguration()->registerMigrationsFromDirectory($path);
    }

    /**
     * Register a single migration version to be executed by a AbstractMigration
     * class.
     *
     * @param string $version  The version of the migration in the format YYYYMMDDHHMMSS.
     * @param string $class    The migration class to execute for the version.
     */
    public function registerMigration($version, $class)
    {
        return $this->getMainConfiguration()->registerMigration($version, $class);
    }

    /**
     * Register an array of migrations. Each key of the array is the version and
     * the value is the migration class name.
     *
     *
     * @param array $migrations
     * @return void
     */
    public function registerMigrations(array $migrations)
    {
        return $this->getMainConfiguration()->registerMigrations($migrations);
    }

    /**
     * Get the array of registered migration versions.
     *
     * @return array $migrations
     */
    public function getMigrations()
    {
        $migrations = array();
        foreach($this->getConfigurations() as $config) {
            // TODO: find a better way to merge these
            foreach($config->getMigrations() as $v => $mig) {
                $migrations[$v]  = $mig;
            }
        }
        ksort($migrations);
        return $migrations;
    }

    /**
     * Returns the Version instance for a given version in the format YYYYMMDDHHMMSS.
     *
     * @param string $version   The version string in the format YYYYMMDDHHMMSS.
     * @return Version $version
     * @throws MigrationException $exception Throws exception if migration version does not exist.
     */
    public function getVersion($version)
    {
        foreach($this->getConfigurations() as $config) {
            if ($config->hasVersion($version)) {
                return $config->getVersion($version);
            }
        }
        throw MigrationException::unknownMigrationVersion($version);
    }

    /**
     * Check if a version exists.
     *
     * @param string $version
     * @return bool $exists
     */
    public function hasVersion($version)
    {
        foreach ($this->getConfigurations() as $config) {
            if ($config->hasVersion($version)) {
                return true;
            } 
        }
        return false;
    }

    /**
     * Check if a version has been migrated or not yet
     *
     * @param Version $version
     * @return bool $migrated
     */
    public function hasVersionMigrated(Version $version)
    {
        foreach ($this->getConfigurations() as $config) {
            if ($config->hasVersionMigrated($version)) {
                return true;
            } 
        }
        return false;
    }

    /**
     * Returns all migrated versions from the versions table, in an array.
     *
     * @return array $migrated
     */
    public function getMigratedVersions()
    {
        $version = array();
        foreach($this->getConfigurations() as $config) {
            $version = array_merge($version, $config->getMigratedVersions());
        }
        return $version;
    }
    
    /**
     * Returns an array of available migration version numbers.
     *
     * @return array $availableVersions
     */
    public function getAvailableVersions()
    {
        $availableVersions = array();
        foreach ($this->getConfigurations() as $config) {
            $availableVersions = array_merge($availableVersions, $config->getAvailableVersions());
        }
        return $availableVersions;
    }

    /**
     * Returns the current migrated version from the versions table.
     *
     * @return bool $currentVersion
     */
    public function getCurrentVersion()
    {
        $versions = array();
        foreach($this->getConfigurations() as $config) {
            $versions[] = $config->getCurrentVersion();
        }
        sort($versions);
        return array_pop($versions);
    }

    /**
     * Returns the total number of executed migration versions
     *
     * @return integer $count
     */
    public function getNumberOfExecutedMigrations()
    {
        $num = 0;
        foreach($this->getConfigurations() as $config) {
            $num += $config->getNumberOfExecutedMigrations();
        }
        return $num;
    }

    /**
     * Returns the total number of available migration versions
     *
     * @return integer $count
     */
    public function getNumberOfAvailableMigrations()
    {
        $num = 0;
        foreach($this->getConfigurations() as $config) {
            $num += $config->getNumberOfAvailableMigrations();
        }
        return $num;
    }

    /**
     * Returns the latest available migration version.
     *
     * @return string $version  The version string in the format YYYYMMDDHHMMSS.
     */
    public function getLatestVersion()
    {
        $versions = array();
        foreach ($this->getConfigurations() as $config) {
            $versions[] = $config->getLatestVersion();
        }
        sort($versions);
        return array_pop($versions);
    }

    /**
     * Create the migration table to track migrations with.
     *
     * @return bool $created  Whether or not the table was created.
     */
    public function createMigrationTable()
    {
        $return = false;
        foreach ($this->getConfigurations() as $config) {
            if ($config->createMigrationTable()) {
                $return = true;
            }
        }
        return $return;
    }

    /**
     * Returns the array of migrations to executed based on the given direction
     * and target version number.
     *
     * @param string $direction    The direction we are migrating.
     * @param string $to           The version to migrate to.
     * @return array $migrations   The array of migrations we can execute.
     */
    public function getMigrationsToExecute($direction, $to)
    {
        $migrations = array();
        foreach($this->getConfigurations() as $config) {
            $migrations = array_merge($migrations, $config->getMigrationsToExecute($direction, $to));
        }
        return $migrations;
    }
}