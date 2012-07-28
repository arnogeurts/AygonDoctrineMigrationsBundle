<?php

namespace Aygon\DoctrineMigrationsBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add the container to 
 */
abstract class ContainerAwareMigration extends AbstractMigration
{
    /**
     * The DI container
     * @var ContainerInterface 
     */
    private $container;
    
    /**
     * Set the DI container
     * 
     * @param ContainerInterface $container 
     */
    public function setContainer(ContainerInterface $container = null) 
    {
        $this->container = $container;
    }
    
    /**
     * Get the service container
     * 
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }
}
