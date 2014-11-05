<?php

use Heri\Bundle\JobQueueBundle\Tests\TestCase;

class QueueServiceTest extends TestCase
{
    public function testPush()
    {
        $queue = $this->container->get('jobqueue');
        $queue->attach('my:queue1');

        $queue->push(array(
            'command' => 'cache:clear',
            'argument' => array(
                '--env' => 'test'
            )
        ));

        $messages = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Message')
            ->findAll();
        $this->assertEquals(1, count($messages));

        $message = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Message')
            ->find(1);

        $this->assertEquals(array(
            'command' => 'cache:clear',
            'argument' => array(
                '--env' => 'test'
            )
        ), json_decode($message->getBody(), true));
    }

}
