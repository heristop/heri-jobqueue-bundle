<?php

namespace Heri\JobQueueBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Heri\JobQueueBundle\Entity\Queue;

class Fixtures implements FixtureInterface
{
    public function load($manager)
    {
        $queue = new Queue();
        $queue->setQueueName('erp:front');
        $queue->setTimeout(90);
        $manager->persist($queue);
        $manager->flush();
    }
}