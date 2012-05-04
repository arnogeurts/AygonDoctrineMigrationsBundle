<?php

namespace Aygon\DoctrineMigrationsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;

/**
 * Command for generate migration classes by comparing your current database schema
 * to your mapping information.
 *
 * @author Arno Geurts
 */
class MigrationsDiffDoctrineCommand extends DiffCommand
{
    protected function configure()
    {
        parent::configure();
        DoctrineCommand::reconfigureCommand($this);
		
        $this
            ->setName('doctrine:migrations:diff')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a migration by comparing your current database to your mapping information:

    <info>%command.full_name% name </info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% name --editor-cmd=mate</info>
EOT
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // set application helpers
        DoctrineCommandHelper::setApplicationEntityManager($this->getApplication(), $input->getOption('em'));
        DoctrineCommand::preExecuteCommand($this, $input, $output);
        parent::execute($input, $output);
    }
}
