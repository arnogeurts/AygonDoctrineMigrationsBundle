<?php
/*
 * This file is part of the DoctrineMigrationsBundle package.
 *
 * (c) Arno Geurts
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Aygon\DoctrineMigrationsBundle\Migrations\Configuration;

use Aygon\DoctrineMigrationsBundle\Migrations\NamedVersion;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author      Arno Geurts
 */
class NamedConfiguration extends Configuration implements ContainerAwareInterface
{
    /**
     * The DI container
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * Flag for whether or not the migration table has been created
     *
     * @var bool
     */
    private $migrationTableCreated = false;

    /**
     * Array of the registered migrations
     *
     * @var array
     */
    private $migrations = array();

    /**
     * Construct a migration configuration object.
     *
     * @param string $name                The name of the Configuration
     * @param Connection $connection      A Connection instance
     * @param OutputWriter $outputWriter  A OutputWriter instance
     */
    public function __construct($name, Connection $connection, OutputWriter $outputWriter = null)
    {
        parent::__construct($connection, $outputWriter);
        $this->setName($name);
    }
    
    /** 
     * set the DI container
     * 
     * @param Container $container
     * @return void;
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
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
        $version = (string) $version;
        $class = (string) $class;
        if (isset($this->migrations[$version])) {
            throw MigrationException::duplicateMigrationVersion($version, get_class($this->migrations[$version]));
        }
        $version = new NamedVersion($this, $version, $class);
        
        // set optional container in migration
        if($version->getMigration() instanceof ContainerAwareInterface) {
            $version->getMigration()->setContainer($this->container);
        }
        
        $this->migrations[$version->getVersion()] = $version;
        ksort($this->migrations);
        return $version;
    }

    /**
     * Get the array of registered migration versions.
     *
     * @return array $migrations
     */
    public function getMigrations()
    {
        return $this->migrations;
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
        if ( ! isset($this->migrations[$version])) {
            throw MigrationException::unknownMigrationVersion($version);
        }
        return $this->migrations[$version];
    }

    /**
     * Check if a version exists.
     *
     * @param string $version
     * @return bool $exists
     */
    public function hasVersion($version)
    {
        return isset($this->migrations[$version]) ? true : false;
    }

    /**
     * Check if a version has been migrated or not yet
     *
     * @param Version $version
     * @return bool $migrated
     */
    public function hasVersionMigrated(Version $version)
    {
        $this->createMigrationTable();

        $version = $this->getConnection()->fetchColumn("SELECT version FROM " . $this->getMigrationsTableName() . " WHERE version = ? AND name = ?", array($version->getVersion(), $this->getName()));
        return $version !== false ? true : false;
    }

    /**
     * Returns all migrated versions from the versions table, in an array.
     *
     * @return array $migrated
     */
    public function getMigratedVersions()
    {
        $this->createMigrationTable();

        $ret = $this->getConnection()->fetchAll("SELECT version FROM " . $this->getMigrationsTableName() . " WHERE name = ?", array($this->getName()));
        $versions = array();
        foreach ($ret as $version) {
            $versions[] = current($version);
        }

        return $versions;
    }
    
    /**
     * Returns an array of available migration version numbers.
     *
     * @return array $availableVersions
     */
    public function getAvailableVersions()
    {
        $availableVersions = array();
        foreach ($this->migrations as $migration) {
            $availableVersions[] = $migration->getVersion();
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
        $this->createMigrationTable();

        if(sizeof($this->migrations) < 1) {
            return '0';
        }
        
        $migratedVersions = array();
        foreach ($this->migrations as $migration) {
            $migratedVersions[] = $migration->getVersion();
        }
        
        $sql = "SELECT version FROM " . $this->getMigrationsTableName() . " WHERE name = ? AND version IN (" . implode(', ', $migratedVersions) . ") ORDER BY version DESC";
        $sql = $this->getConnection()->getDatabasePlatform()->modifyLimitQuery($sql, 1);
        $result = $this->getConnection()->fetchColumn($sql, array($this->getName()));
        return $result !== false ? (string) $result : '0';
    }

    /**
     * Returns the total number of executed migration versions
     *
     * @return integer $count
     */
    public function getNumberOfExecutedMigrations()
    {
        $this->createMigrationTable();

        $result = $this->getConnection()->fetchColumn("SELECT COUNT(version) FROM " . $this->getMigrationsTableName() . " WHERE name = ?", array($this->getName()));
        return $result !== false ? $result : 0;
    }

    /**
     * Returns the total number of available migration versions
     *
     * @return integer $count
     */
    public function getNumberOfAvailableMigrations()
    {
        return count($this->migrations);
    }

    /**
     * Returns the latest available migration version.
     *
     * @return string $version  The version string in the format YYYYMMDDHHMMSS.
     */
    public function getLatestVersion()
    {
        $versions = array_keys($this->migrations);
        $latest = end($versions);
        return $latest !== false ? (string) $latest : '0';
    }

    /**
     * Create the migration table to track migrations with.
     *
     * @return bool $created  Whether or not the table was created.
     */
    public function createMigrationTable()
    {
        $this->validate();

        if ($this->migrationTableCreated) {
            return false;
        }

        $schema = $this->getConnection()->getSchemaManager()->createSchema();
        if ( ! $schema->hasTable($this->getMigrationsTableName())) {
            $columns = array(
                'version'  => new Column('version', Type::getType('string'), array('length' => 255)),
				'name'     => new Column('name', Type::getType('string'), array('length' => 255)),
            );
            $table = new Table($this->getMigrationsTableName(), $columns);
            $table->setPrimaryKey(array('version'));
            $this->getConnection()->getSchemaManager()->createTable($table);

            $this->migrationTableCreated = true;

            return true;
        }
		$this->migrationTableCreated = true;
		
        return false;
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
        if ($direction === 'down') {
            if (count($this->migrations)) {
                $allVersions = array_reverse(array_keys($this->migrations));
                $classes = array_reverse(array_values($this->migrations));
                $allVersions = array_combine($allVersions, $classes);
            } else {
                $allVersions = array();
            }
        } else {
            $allVersions = $this->migrations;
        }
        $versions = array();
        $migrated = $this->getMigratedVersions();
        foreach ($allVersions as $version) {
            if ($this->shouldExecuteMigration($direction, $version, $to, $migrated)) {
                $versions[$version->getVersion()] = $version;
            }
        }
        return $versions;
    }

    /**
     * Check if we should execute a migration for a given direction and target
     * migration version.
     *
     * @param string $direction   The direction we are migrating.
     * @param Version $version    The Version instance to check.
     * @param string $to          The version we are migrating to.
     * @param array $migrated     Migrated versions array.
     * @return void
     */
    private function shouldExecuteMigration($direction, Version $version, $to, $migrated)
    {
        if ($direction === 'down') {
            if ( ! in_array($version->getVersion(), $migrated)) {
                return false;
            }
            return $version->getVersion() > $to ? true : false;
        } else if ($direction === 'up') {
            if (in_array($version->getVersion(), $migrated)) {
                return false;
            }
            return $version->getVersion() <= $to ? true : false;
        }
    }
}
