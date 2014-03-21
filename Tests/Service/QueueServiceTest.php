<?php

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
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
        
        $this->assertRegExp('/Cleaned exceptions/', $commandTester->getDisplay());
    }
}