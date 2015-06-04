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

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Process;
use Heri\Bundle\JobQueueBundle\Exception\CommandFindException;
use Heri\Bundle\JobQueueBundle\Exception\BadFormattedMessageException;

class QueueService
{
    const MICROSECONDS_PER_SECOND = 1000000;

    /**
     * var ZendQueue\Adapter\AbstractAdapter.
     */
    public $adapter;

    /**
     * var LoggerInterface.
     */
    protected $logger;

    /**
     * var ContainerAwareCommand.
     */
    protected $command;

    /**
     * var OutputInterface.
     */
    protected $output;

    /**
     * var \ZendQueue\Queue.
     */
    protected $queue;

    /**
     * var array.
     */
    protected $config;

    /**
     * var bool.
     */
    protected $running;

    /**
     * @param LoggerInterface $logger
     * @param array           $config
     */
    public function __construct(LoggerInterface $logger, array $config = array())
    {
        $this->logger = $logger;
        $this->config = $config;

        $this->running = true;
        $this->output = new ConsoleOutput();

        if (php_sapi_name() == 'cli') {
            pcntl_signal(SIGTERM, function () {
                $this->stop();
            });

            pcntl_signal(SIGINT, function () {
                $this->stop();
            });
        }
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
        $this->queue = new \ZendQueue\Queue($this->adapter, [
            'name' => $name,
        ]);
    }

    /**
     * @param int $maxMessages
     * @param int $timeout
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
            if (class_exists('Zend\Json\Encoder')) {
                $body = \Zend\Json\Encoder::encode($args);
            } else {
                $body = json_encode($args);
            }

            $this->queue->send($body);
            $this->output->writeLn("<fg=green> [x] [{$this->queue->getName()}] {$args['command']} sent</>");
        }
    }

    /**
     * @param string $name
     * @param int    $timeout
     */
    public function create($name, $timeout = null)
    {
        return $this->adapter->create($name, $timeout);
    }

    /**
     * @param string $name
     *
     * @return bool
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
     * @return bool
     */
    public function flush()
    {
        return $this->adapter->flush();
    }

    /**
     * @return int
     */
    public function countMessages()
    {
        return $this->adapter->count();
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->adapter->count();
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

    public function isRunning()
    {
        return $this->running;
    }

    public function listen($name = null, $sleep = 0, $work = true)
    {
        if ($work) {
            $this->loop($name);
        } else {
            // event loop
            if (class_exists('React\EventLoop\Factory')) {
                $loop = \React\EventLoop\Factory::create();
                $loop->addPeriodicTimer($sleep, function (\React\EventLoop\Timer\Timer $timer) use ($name) {
                    $this->loop($name);
                    // stop closure loop on SIGINT
                    if (!$this->isRunning()) {
                        $timer->getLoop()->stop();
                    }
                });
                $loop->run();
            } else {
                do {
                    $this->loop($name);
                    usleep($sleep * self::MICROSECONDS_PER_SECOND);
                } while ($this->running);
            }
        }
    }

    protected function stop()
    {
        $this->running = false;
    }

    protected function loop($name = null)
    {
        $listQueues = [];

        if (php_sapi_name() == 'cli') {
            pcntl_signal_dispatch();
        }
        if (!$this->isRunning()) {
            return;
        }

        if ($name) {
            $listQueues[] = $name;
        } else {
            $listQueues = $this->config['queues'];
        }

        foreach ($listQueues as $name) {
            $this->attach($name);
            $this->receive($this->config['max_messages']);

            if (php_sapi_name() == 'cli') {
                pcntl_signal_dispatch();
            }
            if (!$this->isRunning()) {
                return;
            }
        }
    }

    /**
     * @param MessageIterator $messages
     */
    protected function handle(\ZendQueue\Message\MessageIterator $messages)
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
        if (property_exists($this->adapter, 'logger')) {
            $this->adapter->logger = $this->logger;
        }

        try {
            list(
                $commandName,
                $arguments
            ) = $this->getMessageFormattedBody($message);

            $this->output->writeLn("<fg=yellow> [x] [{$this->queue->getName()}] {$commandName} received</> ");

            $process = new Process(sprintf('%s %s %s %s',
                '/usr/bin/php', 'app/console', $commandName,
                implode(' ', $arguments)
            ));
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            print $process->getOutput();

            $this->queue->deleteMessage($message);
            $this->output->writeLn("<fg=green> [x] [{$this->queue->getName()}] {$commandName} done</>");
        } catch (\RuntimeException $e) {
            $this->output->writeLn("<fg=white;bg=red> [!] [{$this->queue->getName()}] FAILURE: {$e->getMessage()}</>");
            $this->adapter->logException($message, $e);
        }
    }

    /**
     * @param Zend_Message $message
     */
    protected function getMessageFormattedBody($message)
    {
        if (class_exists('Zend\Json\Json')) {
            $body = \Zend\Json\Json::decode($message->body, true);
        } else {
            $body = json_decode($message->body, true);
        }

        $arguments = array();
        if (isset($body['argument'])) {
            $args = $body['argument'];
        } elseif (isset($body['arguments'])) {
            $args = $body['arguments'];
        }

        if (!isset($body['command'])) {
            throw new BadFormattedMessageException('Command name not found');
        }

        $commandName = $body['command'];
        foreach ($args as $key => $value) {
            if (is_string($key) && preg_match('/^--/', $key)) {
                if (is_bool($value)) {
                    $arguments[] = "{$key}";
                } else {
                    $arguments[] = "{$key}={$value}";
                }
            } else {
                $arguments[] = $value;
            }
        }

        return array(
            $commandName,
            $arguments,
        );
    }
}
