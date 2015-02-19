<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\Service;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Heri\Bundle\JobQueueBundle\Exception\CommandFindException;
use Heri\Bundle\JobQueueBundle\Exception\BadFormattedMessageException;

use ZendQueue\Message\MessageIterator;

class QueueService
{
    /**
     * var ZendQueue\Adapter\AbstractAdapter
     */
    public $adapter;

    /**
     * var LoggerInterface
     */
    protected $logger;

    /**
     * var ContainerAwareCommand
     */
    protected $command;

    /**
     * var OutputInterface
     */
    protected $output;

    /**
     * var \ZendQueue\Queue
     */
    protected $queue;

    /**
     * var array
     */
    protected $config;

    /**
     * @param LoggerInterface $logger
     * @param array $config
     */
    public function __construct(LoggerInterface $logger, array $config = array())
    {
        $this->logger = $logger;
        $this->config = $config;

        $this->output = new ConsoleOutput();
    }

    public function setUp($config)
    {
        $this->config = $config;
    }

    /**
     * @param string $name
     */
    public function attach($name)
    {
        $this->queue = new \ZendQueue\Queue($this->adapter, array(
            'name' => $name,
        ));
    }

    /**
     * @param integer $maxMessages
     * @param integer $timeout
     */
    public function receive($maxMessages = 1, $timeout = null)
    {
        $messages = $this->queue->receive($maxMessages, $timeout, $this->queue);

        if ($messages && $messages->count() > 0) {
            $this->handle($messages);
        }
    }

    /**
     * @param array $args
     */
    public function push(array $args)
    {
        if (!is_null($this->queue)) {
            $this->queue->send(\Zend\Json\Encoder::encode($args));

            $this->output->writeLn("<fg=green> [x] [{$this->queue->getName()}] {$args['command']} sent</>");
        }
    }

    /**
     * @param string  $name
     * @param integer $timeout
     */
    public function create($name, $timeout = null)
    {
        return $this->adapter->create($name, $timeout);
    }

    /**
     * @param string  $name
     * @return boolean
     */
    public function delete($name)
    {
        return $this->adapter->delete($name);
    }

    /**
     * @param string $queueName
     */
    public function showMessages($queueName)
    {
        return $this->adapter->showMessages($queueName);
    }

    /**
     * @return boolean
     */
    public function flush()
    {
        return $this->adapter->flush();
    }

    /**
     * @param ContainerAwareCommand $command
     */
    public function setCommand(ContainerAwareCommand $command)
    {
        $this->command = $command;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    public function isEnabled()
    {
        return $this->config['enabled'];
    }

    public function listen($name = null, $sleep = null, $work = true)
    {
        if ($work) {
            $this->loop($name);
        } else {
            // event loop
            if (class_exists('React\EventLoop\Factory')) {
                $loop = \React\EventLoop\Factory::create();
                $loop->addPeriodicTimer($sleep, function($this) use ($name) {  $this->loop($name); });
                $loop->run();
            } else {
                do {
                    $this->loop($name);
                    sleep($sleep);
                } while (true);
            }
        }
    }

    protected function loop($name = null) 
    {
        $listQueues = array();

        if ($name) {
            $listQueues[] = $name;
        } else {
            $listQueues = $this->config['queues'];
        }

        foreach ($listQueues as $name) {
            $this->attach($name);
            $this->receive($this->config['max_messages']);
        }
    }

    /**
     * @param MessageIterator $messages
     */
    protected function handle(MessageIterator $messages)
    {
        if (!$this->output instanceof OutputInterface) {
            $this->output = new StreamOutput(fopen('php://memory', 'w', false));
        }

        if (!$this->command instanceof ContainerAwareCommand) {
            throw new CommandFindException('Cannot load command');
        }

        foreach ($messages as $message) {
            $this->run($message);
        }
    }

    protected function run($message)
    {
        try {

            list(
                $commandName, 
                $arguments
            ) = $this->getMessageFormattedBody($message);

            $this->output->writeLn("<fg=yellow> [x] [{$this->queue->getName()}] {$commandName} received</> ");

            $input = new ArrayInput($arguments);
            $command = $this->command->getApplication()->find($commandName);
            $command->run($input, $this->output);

            $this->queue->deleteMessage($message);
            $this->output->writeLn("<fg=green> [x] [{$this->queue->getName()}] {$commandName} done</>");

        } catch (\Exception $e) {

            $this->output->writeLn("<fg=white;bg=red> [!] [{$this->queue->getName()}] FAILURE: {$e->getMessage()}</>");
            $this->adapter->logException($message, $e);

        }
    }

    /**
     * @param Zend_Message $message
     */
    protected function getMessageFormattedBody($message)
    {
        $body = \Zend\Json\Json::decode($message->body, true);

        $arguments = array();
        if (isset($body['argument'])) {
            $arguments = $body['argument'];
        } elseif (isset($body['arguments'])) {
            $arguments = $body['arguments'];
        }

        if (!isset($body['command'])) {
            throw new BadFormattedMessageException('Command name not found');
        }

        return array(
            $body['command'],
            array_merge(array(''), $arguments)
        );
    }

    public function countMessages()
    {
        return $this->adapter->countMessages();
    }

}
