<?php
/*
 * This file is part of the DoctrineMigrationsBundle package.
 *
 * (c) Arno Geurts
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Aygon\DoctrineMigrationsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;

/**
 * Command for executing a migration to a specified version or the latest available version.
 *
 * @author Arno Geurts
 */
class MigrationsMigrateDoctrineCommand extends MigrateCommand
{
    protected function configure()
    {
        parent::configure();
        DoctrineCommand::removeConfigurationOptionsFromCommand($this);
        
        $this
            ->setName('doctrine:migrations:migrate')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // set application helpers
        DoctrineCommandHelper::setApplicationEntityManager($this->getApplication(), $input->getOption('em'));
        DoctrineCommand::preExecuteCommand($this, $input, $output);
        parent::execute($input, $output);
    }
}
