<?php

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Heri\Bundle\JobQueueBundle\Tests\TestCase;
use Heri\Bundle\JobQueueBundle\Command\QueueCreateCommand;
use Heri\Bundle\JobQueueBundle\Command\QueueDeleteCommand;

class QueueDeleteCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = new Application($this->kernel);
        $application->add(new QueueCreateCommand());

        $command = $application->find('jobqueue:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'queue-name' => 'my:queue1',
            '--timeout' => 10,
        ), array('interactive' => false));

        $application->add(new QueueDeleteCommand());

        $command = $application->find('jobqueue:delete');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'queue-name' => 'my:queue1',
        ), array('interactive' => false));

        $this->assertRegExp('/Queue "my:queue1" deleted/', $commandTester->getDisplay(), 'Deleted queue');
    }

}
