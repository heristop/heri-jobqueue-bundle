<?php

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Heri\Bundle\JobQueueBundle\Tests\TestCase;
use Heri\Bundle\JobQueueBundle\Command\QueueShowCommand;

class QueueShowCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = new Application($this->kernel);
        $application->add(new QueueShowCommand());

        $command = $application->find('jobqueue:show');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'queue-name' => 'toto',
        ]);

        $this->assertRegExp('/| id | body | created | ended | failed |/', $commandTester->getDisplay());
    }
}
