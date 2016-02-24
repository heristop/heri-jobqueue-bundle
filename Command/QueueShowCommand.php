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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class QueueShowCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('jobqueue:show')
            ->setDescription('List jobs in a queue')
            ->addArgument(
                'queue-name',
                InputArgument::REQUIRED
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getContainer()->get('jobqueue');
        $messages = $queue->showMessages($input->getArgument('queue-name'));

        $table = new Table($output);
        $table
            ->setHeaders(array('id', 'body', 'created', 'ended', 'failed'))
            ->setRows($messages)
        ;
        $table->render($output);
    }
}
