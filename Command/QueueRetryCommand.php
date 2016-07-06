<?php

/**
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueRetryCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('jobqueue:retry')
            ->setDescription('Retrying Failed Jobs')
            ->addArgument('queue-name', InputArgument::REQUIRED, 'Which name do you want for the queue?')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getContainer()->get('jobqueue');
        $name = $input->getArgument('queue-name');

        if ($queue->retry($name)) {
            $output->writeLn(sprintf('<info>Retried failed jobs for %s deleted</info>', $name));
        }
    }
}
