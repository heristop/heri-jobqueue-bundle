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

use Heri\Bundle\JobQueueBundle\Entity\Queue;

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
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue  = $this->getContainer()->get('jobqueue');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        
        $dialog = $this->getHelperSet()->get('dialog');
        $timeout = $dialog->ask(
            $output,
            '<question>Please enter the timeout</question> [<comment>90</comment>]: ',
            90
        );
        
        $name = $input->getArgument('queue-name');
        $queue = $em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Queue')
            ->findOneBy(array(
                'name' => $name
            ));
        if (!$queue) {
            $queue = new Queue();
            $queue->setName($name);
            $action = "created";
        } else {
            $action = "updated";
        }
        $queue->setTimeout($timeout);
        $em->persist($queue);
        $em->flush();
        
        $output->writeLn('<info>Queue "'.$name.'" '.$action.'</info>');
    }
}
