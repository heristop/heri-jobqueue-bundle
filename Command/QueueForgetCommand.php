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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueForgetCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('jobqueue:forget')
            ->setDescription('Retrying Failed Jobs')
            ->addOption('record', null, InputOption::VALUE_NONE, 'Id of failed message to forget');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getContainer()->get('jobqueue');
        $id = $input->getOption('record');

        if ($queue->forget($id)) {
            $output->writeLn(sprintf('<info>Forgotten message %s</info>', $id));
        }
    }
}
