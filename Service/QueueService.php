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
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $name
     */
    public function attach($name)
    {
        $this->config = array(
            'name' => $name,
        );

        if (!$this->queue instanceof \ZendQueue\Queue) {
            $this->queue = new \ZendQueue\Queue($this->adapter, $this->config);
        } else {
            $this->queue->createQueue($name);
        }
    }

    /**
     * @param integer $maxMessages
     */
    public function receive($maxMessages = 1)
    {
        $messages = $this->queue->receive($maxMessages);

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
     * @param \ZendQueue\Queue $queue
     */
    public function showMessages(\ZendQueue\Queue $queue)
    {
        return $this->adapter->showMessages($queue);
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
        if (!$this->output instanceof OutputInterface) {
            $this->output = new StreamOutput(fopen('php://memory', 'w', false));
        }

        return $this->output;
    }

    /**
     * @param MessageIterator $messages
     */
    protected function handle(MessageIterator $messages)
    {
        if (!$this->command instanceof ContainerAwareCommand) {
            throw new CommandFindException('Cannot load command');
        }

        foreach ($messages as $message) {
            $this->run($message);
        }
    }

    protected function run($message)
    {
        $output = $this->getOutput();

        $date = new \DateTime("now");
        $output->writeLn(sprintf(
            "<fg=yellow>%s - %s [%s]</fg=yellow>",
            $date->format("H:i:s"),
            ($message->failed ? 'failed' : 'new'),
            $message->id
        ));

        try {

            list($commandName, $arguments) = $this->getFormattedBody($message->body);
            $input = new ArrayInput($arguments);
            $command = $this->command->getApplication()->find($commandName);
            $command->run($input, $output);

            $this->queue->deleteMessage($message);
            $output->writeLn("<fg=green>Ended</fg=green>");

        } catch (\Exception $e) {

            $this->adapter->logException($message, $e);
            $output->writeLn("<fg=red>Failed</fg=red> {$e->getMessage()}");

        }
    }

    protected function getFormattedBody($encodedBody)
    {
        $body = \Zend\Json\Json::decode($encodedBody, true);

        $arguments = array();
        if (isset($body['argument'])) {
            $argument = $body['argument'];
        }

        if (!isset($body['command'])) {
            throw new BadFormattedMessageException('Command name not found');
        }

        return array(
            $body['command'],
            array_merge(array(''), $arguments)
        );
    }

}
