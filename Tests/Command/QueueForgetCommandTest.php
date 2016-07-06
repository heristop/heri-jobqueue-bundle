<?php

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Heri\Bundle\JobQueueBundle\Tests\TestCase;
use Heri\Bundle\JobQueueBundle\Command\QueueForgetCommand;

class QueueForgetCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = new Application($this->kernel);
        $application->add(new QueueForgetCommand());

        $command = $application->find('jobqueue:forget');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertRegExp('//', $commandTester->getDisplay());
    }
}
