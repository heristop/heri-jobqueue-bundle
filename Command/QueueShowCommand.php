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

use Heri\Bundle\JobQueueBundle\Entity\Queue;
use Heri\Bundle\JobQueueBundle\Entity\Message;

class QueueShowCommand extends ContainerAwareCommand
{
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue  = $this->getContainer()->get('jobqueue');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb
            ->select('m.id, m.body, m.created, m.ended, m.failed')
            ->from('Heri\Bundle\JobQueueBundle\Entity\Message', 'm')
            ->leftJoin('m.queue', 'Queue')
            ->where($qb->expr()->eq('Queue.name', ':name'))
            ->setParameter('name', $input->getArgument('queue-name'))
        ;

        $query = $qb->getQuery();
        $messages = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $table = $this->getApplication()->getHelperSet()->get('table');
        $table
            ->setHeaders(array('id', 'body', 'created', 'ended', 'failed'))
            ->setRows($messages)
        ;
        $table->render($output);
    }
}
