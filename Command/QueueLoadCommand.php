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

class QueueLoadCommand extends ContainerAwareCommand
{
    protected $output;
    
    protected function configure()
    {
        $this
            ->setName('jobqueue:load')
            ->setDescription('Initialize JobQueue manager')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue  = $this->getContainer()->get('jobqueue');
        $config = $this->getContainer()->getParameter('jobqueue.config');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->output = $output;
        
        if ($config['enabled']) {
            $output->writeLn('<info>JobQueue running... press ctrl-c to stop.</info>');
            
            $listenQueues = function() use($config, $queue) {
                foreach ($config['queues'] as $name) {
                    $queue->configure($name);
                    $queue->receive($config['max_messages']);
                }
            };
            
            // event loop
            if (class_exists('React\EventLoop\Factory')) {
                $loop = \React\EventLoop\Factory::create();
                $loop->addPeriodicTimer(1, $listenQueues());
                $loop->run();
            } else {
                do {
                    $listenQueues();
                    sleep(1);
                } while (true);
            }
        } else {
            $output->writeLn('<comment>JobQueue manager deactivated</comment>');
        }
    }
    
    public function getOutput()
    {
        return $this->output;
    }
}
