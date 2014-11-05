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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueListenCommand extends ContainerAwareCommand
{
    protected
        $output,
        $running,
        $work
    ;

    protected function configure()
    {
        $this
            ->setName('jobqueue:listen')
            ->setDescription('Initialize JobQueue manager')
            ->addArgument(
                'queue-name',
                InputArgument::OPTIONAL,
                'Listen a specific queue'
            )
            ->addOption(
                'sleep',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of seconds to wait before polling for new job',
                1
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->running = true;

        $queue  = $this->getContainer()->get('jobqueue');
        $config = $this->getContainer()->getParameter('jobqueue.config');
        $queue->setCommand($this);
        $queue->setOutput($output);
        $sleep = $input->getOption('sleep');

        if ($config['enabled']) {
            $listenQueues = function () use ($input, $config, $queue) {
                $queues = array();
                $inputName = $input->getArgument('queue-name');
                if ($inputName) {
                    $queues[] = $inputName;
                } else {
                    $queues = $config['queues'];
                }

                foreach ($queues as $name) {
                    $queue->attach($name);
                    $queue->receive($config['max_messages']);
                }
            };

            if ($this->work) {
                $listenQueues();
                $output->writeLn('<info>Processed the first job on the queue</info>');
            } else {
                $output->writeLn('<info>JobQueue running... press ctrl-c to stop.</info>');

                // event loop
                if (class_exists('React\EventLoop\Factory')) {
                    $loop = \React\EventLoop\Factory::create();
                    $loop->addPeriodicTimer($sleep, $listenQueues());
                    $loop->run();
                } else {
                    do {
                        $listenQueues();
                        sleep($sleep);
                    } while ($this->running);
                }
            }
        } else {
            $output->writeLn('<comment>JobQueue manager deactivated</comment>');
        }
    }

}
