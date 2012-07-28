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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand;

/**
 * Command for executing single migrations up or down manually.
 *
 * @author Arno Geurts
 */
class MigrationsExecuteDoctrineCommand extends ExecuteCommand
{
    protected function configure()
    {
        parent::configure();
        DoctrineCommand::removeConfigurationOptionsFromCommand($this);
        
        $this
            ->setName('doctrine:migrations:execute')
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
