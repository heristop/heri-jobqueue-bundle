<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCreateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('jobqueue:create')
            ->setDescription('Create a queue')
            ->addArgument(
                'queue-name',
                InputArgument::REQUIRED,
                'Which name do you want for the queue?'
            )
            ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'Timeout')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getContainer()->get('jobqueue');

        $timeout = $input->getOption('timeout');
        $name = $input->getArgument('queue-name');

        $dialog = $this->getHelperSet()->get('dialog');
        if (!$timeout) {
            $timeout = $dialog->ask(
                $output,
                '<question>Please enter the timeout</question> [<comment>90</comment>]: ',
                90
            );
        }

        if ($queue->create($name, $timeout)) {
            $action = "created";
        } else {
            $action = "updated";
        }

        $output->writeLn("<info>Queue \"{$name}\" {$action}</info>");
    }
}
