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

class QueueFlushCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('jobqueue:flush')
            ->setDescription('Delete all of your failed jobs')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getContainer()->get('jobqueue');
        $queue->flush();
        
        $queue->attach('avip:queue');
        
        $queue->push(array(
            'command' => 'cache:clear',
            'argument' => array(
                '--env' => 'test'
            )
        ));
        
        $output->writeLn('<info>Cleaned exceptions</info>');
    }

}
