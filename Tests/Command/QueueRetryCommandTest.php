<?php

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Heri\Bundle\JobQueueBundle\Tests\TestCase;
use Heri\Bundle\JobQueueBundle\Command\QueueRetryCommand;

class QueueRetryCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = new Application($this->kernel);
        $application->add(new QueueRetryCommand());

        $command = $application->find('jobqueue:retry');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'queue-name' => 'toto',
        ]);

        $this->assertRegExp('//', $commandTester->getDisplay());
    }
}
