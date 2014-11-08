<?php

use Heri\Bundle\JobQueueBundle\Tests\TestCase;
use Heri\Bundle\JobQueueBundle\Service\QueueService;

class QueueServiceTest extends TestCase
{
    public function testInstanceOf()
    {
        $queue = $this->container->get('jobqueue');
        $this->assertInstanceOf('\Heri\Bundle\JobQueueBundle\Service\QueueService', $queue);
    }

}
