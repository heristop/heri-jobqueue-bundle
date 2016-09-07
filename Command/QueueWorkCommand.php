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

class QueueWorkCommand extends QueueListenCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->work = true;
        $this
            ->setName('jobqueue:work')
            ->setDescription('Process only the first job on the queue')
        ;
    }
}
