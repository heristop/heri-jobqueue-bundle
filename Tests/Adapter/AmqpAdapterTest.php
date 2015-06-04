<?php

use Heri\Bundle\JobQueueBundle\Tests\TestCase;
use Heri\Bundle\JobQueueBundle\Adapter as Adapter;
use Heri\Bundle\JobQueueBundle\Service\QueueService;
use Heri\Bundle\JobQueueBundle\Command\QueueListenCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

class AmqpAdapterTest extends TestCase
{
    /**
     * @var QueueService
     */
    protected $queue;

    /**
     * @var string
     */
    protected $queueName = 'my:queue';

    /**
     * @var int
     */
    protected $maxMessages = 1;

    /**
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var bool
     */
    protected $verbose = true;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        if (!class_exists('PhpAmqpLib\Message\AMQPMessage')) {
            $this->markTestSkipped('AMQP not installed');
        }

        // set default rabbitmq configuration
        $adapter = new Adapter\AmqpAdapter(array(
            'host' => 'localhost',
            'port' => '5672',
            'user' => 'guest',
            'password' => 'guest',
        ));

        $this->queue = new QueueService(
            $this->container->get('logger'),
            $this->container->getParameter('jobqueue.config')
        );
        $this->queue->adapter = $adapter;
        $this->queue->attach($this->queueName.'1');

        $application = new Application($this->kernel);
        $application->add(new QueueListenCommand());

        $command = $application->find('jobqueue:listen');
        $this->queue->setCommand($command);

        if ($this->verbose) {
            $this->queue->setOutput(new ConsoleOutput());
        }

        $this->channel = $adapter->getChannel();
        $this->connection = $adapter->getConnection();
    }

    public function testPushAndReceive()
    {
        // Queue list command
        $command1 = array(
            'command' => 'list',
        );
        $this->queue->push($command1);

        // Queue demo:great command
        $command2 = array(
            'command' => 'demo:great',
            'argument' => array(
                'name' => 'Alexandre',
                '--yell' => true,
            ),
        );
        $this->queue->push($command2);

        // Run list command using directly receive method
        $this->queue->receive($this->maxMessages);

        // Run demo:great command using listen method
        try {
            $this->queue->listen($this->queueName.'1');
            // $this->queue->receive($this->maxMessages);
        } catch (\Exception $e) {
            $this->assertRegExp(
                '/There are no commands defined in the "demo" namespace/',
                $e->getMessage(),
                'Command not found'
            );
        }
    }

    public function testPerf()
    {
        for ($i = 0; $i < 100; $i++) {
            // Queue list command
            $command = array(
                'command' => 'list',
            );
            $this->queue->push($command);
            $this->queue->receive(10);
        }
    }

    public function tearDown()
    {
        if ($this->channel) {
            $this->channel->queue_delete($this->queueName.'1');
            $this->channel->close();
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
