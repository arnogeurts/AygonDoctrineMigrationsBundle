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
use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;

/**
 * Command for generating new blank migration classes
 *
 * @author Arno Geurts
 */
class MigrationsGenerateDoctrineCommand extends GenerateCommand
{
    protected function configure()
    {
        parent::configure();
        DoctrineCommand::reconfigureCommand($this);
        
        $this
            ->setName('doctrine:migrations:generate')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name% name</info>

You can optionally specify a <comment>--editor-cmd</comment> option to open the generated file in your favorite editor:

    <info>%command.full_name% name --editor-cmd=mate</info>
EOT
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // set application helpers
        DoctrineCommandHelper::setApplicationEntityManager($this->getApplication(), $input->getOption('em'));
        DoctrineCommand::preExecuteCommand($this, $input, $output, true);
        parent::execute($input, $output);
    }
}
