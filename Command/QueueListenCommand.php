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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueListenCommand extends ContainerAwareCommand
{
    /**
     * @param bool
     */
    protected $work;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('jobqueue:listen')
            ->setDescription('Initialize JobQueue manager')
            ->addArgument('queue-name', InputArgument::OPTIONAL, 'Listen a specific queue')
            ->addOption(
                'sleep',
                '0.2',
                InputOption::VALUE_OPTIONAL,
                'Number of seconds to wait before polling for new job',
                1
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getContainer()->get('jobqueue');
        $queue->setUp($this->getContainer()->getParameter('jobqueue.config'));
        $queue->setOutput($output);

        if (!$queue->isEnabled()) {
            $output->writeLn('<comment>JobQueue manager deactivated</comment>');
        } else {
            if ($this->work) {
                $output->writeLn('<comment>Handling the first job on the queue...</comment>');
            } else {
                $output->writeLn('<comment>JobQueue running... press ctrl-c to stop.</comment>');
            }

            $queue->listen(
                $input->getArgument('queue-name'),
                $input->getOption('sleep'),
                $this->work
            );
        }
    }
}
