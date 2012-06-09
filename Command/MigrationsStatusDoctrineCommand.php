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
use Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand;

/**
 * Command to view the status of a set of migrations.
 *
 * @author Arno Geurts
 */
class MigrationsStatusDoctrineCommand extends StatusCommand
{
    protected function configure()
    {
        parent::configure();
        DoctrineCommand::reconfigureCommand($this);

        $this
            ->setName('doctrine:migrations:status')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command outputs the status of a set of migrations for a given bundle:

    <info>%command.full_name% name</info>

You can output a list of all available migrations and their status with <comment>--show-versions</comment>:

    <info>%command.full_name% name --show-versions</info>
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
