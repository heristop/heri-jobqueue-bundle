<?php

use Heri\Bundle\JobQueueBundle\Tests\TestCase;

class QueueServiceTest extends TestCase
{
    public function testInstanceOf()
    {
        $queue = $this->container->get('jobqueue');
        $this->assertInstanceOf('\Heri\Bundle\JobQueueBundle\Service\QueueService', $queue);
    }
}
