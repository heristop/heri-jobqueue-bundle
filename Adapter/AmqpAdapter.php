<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\Adapter;

use ZendQueue\Adapter\AbstractAdapter;
use ZendQueue\Message;
use ZendQueue\Queue;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Heri\Bundle\JobQueueBundle\Exception\AdapterRuntimeException;
use Heri\Bundle\JobQueueBundle\Exception\MissingConfigurationException;
use Heri\Bundle\JobQueueBundle\Exception\UnsupportedMethodCallException;

/**
 * Amqp adapter.
 *
 * @see ZendQueue\Queue_Adapter_AdapterAbstract
 */
class AmqpAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var AMQPConnection
     */
    protected $connection = null;

    /**
     * @var AMQPConnection
     */
    protected $channel = null;

    /**
     * @var AMQP_Queue_Exchange
     */
    protected $exchangeName = null;

    /**
     * @var int count of messages we got last time
     */
    private $_count;

    /**
     * Constructor.
     *
     * @param array|Zend_Config $options options (host, port, login, password)
     * @param null|Queue   $queue
     */
    public function __construct($options, Queue $queue = null)
    {
        parent::__construct($options, $queue);

        if (!class_exists('PhpAmqpLib\Message\AMQPMessage')) {
            throw new \Exception('Please install videlalvaro/php-amqplib dependency');
        }

        if (is_array($options)) {
            try {
                $host = $options['host'];
                $port = $options['port'];
                $user = $options['user'];
                $password = $options['password'];

                $connection = new AMQPConnection($host, $port, $user, $password);
                $channel = $connection->channel();

                $this->connection = $connection;
                $this->channel = $channel;
            } catch (\Exception $e) {
                throw new AdapterRuntimeException("Unable to connect RabbitMQ server: {$e->getMessage()}");
            }
        } else {
            throw new MissingConfigurationException('The options must be an associative array of host, port, login, password...');
        }
    }

    /**
     * Get AMQPConnection object.
     *
     * @return object
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get AMQPChannel object.
     *
     * @return object
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * create queue.
     *
     * @param string $name
     * @param int    $timeout
     *
     * @return boolean
     */
    public function create($name, $timeout = null)
    {
        try {
            /*
                name: $queue
                passive: false
                durable: true // the queue will survive server restarts
                exclusive: false // the queue can be accessed in other channels
                auto_delete: false //the queue won't be deleted once the channel is closed.
            */
            $this->channel->queue_declare($name, false, true, false, false);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * delete queue.
     *
     * @param $name
     *
     * @return bool
     */
    public function delete($name)
    {
        $this->channel->queue_delete($name);

        return true;
    }

    /**
     * Publish message to queue.
     *
     * @param mixed $message (array or string)
     * @param Queue $queue
     *
     * @return boolean|null
     */
    public function send($message, Queue $queue = null)
    {
        if ($queue === null) {
            $queue = $this->_queue;
        }

        if (is_array($message)) {
            $message = \Zend\Json\Encoder::encode($message);
        }

        $this->exchangeName = 'router';

        /*
            name: $exchange
            type: direct
            passive: false
            durable: true // the exchange will survive server restarts
            auto_delete: false //the exchange won't be deleted once the channel is closed.
        */
        $this->channel->exchange_declare($this->exchangeName, 'direct', false, true, false);
        $this->channel->queue_bind($queue->getName(), $this->exchangeName);

        $amqpMessage = new AMQPMessage($message, [
            'content_type' => 'text/plain',
            'delivery_mode' => 2,
        ]);

        $this->channel->basic_publish($amqpMessage, $this->exchangeName);
    }

    /**
     * Get messages in the queue.
     *
     * @param int|null        $maxMessages Maximum number of messages to return
     * @param int|null        $timeout     Visibility timeout for these messages
     * @param null|ZendQueue\Queue $queue
     *
     * @return ZendQueue\MessageIterator
     */
    public function receive($maxMessages = null, $timeout = null, Queue $queue = null)
    {
        $result = [];

        if ($queue === null) {
            $queue = $this->_queue;
        }

        $maxMessages = (int) $maxMessages ? (int) $maxMessages : 1;

        // default approach: GET
        for ($i = $maxMessages; $i > 0; $i--) {
            $amqpMessage = $this->channel->basic_get($queue->getName());

            if (isset($amqpMessage->delivery_info['delivery_tag'])) {
                $result[] = [
                    'body' => $amqpMessage->body,
                    'amqpMessage' => $amqpMessage,
                );
                $this->_count = $amqpMessage->delivery_info['message_count'];
            }
        }

        $options = [
            'queue' => $queue,
            'data' => $result,
            'messageClass' => $queue->getMessageClass(),
        ];

        $classname = $queue->getMessageSetClass();

        return new $classname($options);
    }

    public function getCapabilities()
    {
        return [
            'create' => true,
            'delete' => true,
            'send' => true,
            'count' => true,
            'deleteMessage' => true,
        ];
    }

    /**
     * Does a queue already exist?
     *
     * Use isSupported('isExists') to determine if an adapter can test for
     * queue existance.
     *
     * @param string $name Queue name
     *
     * @return bool
     */
    public function isExists($name)
    {
        return isset($this->_count);
    }

    /**
     * Get an array of all available queues.
     *
     * Not all adapters support getQueues(); use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @return Queue[]
     */
    public function getQueues()
    {
        return [ $this->_queue ];
    }

    /**
     * Return the approximate number of messages in the queue.
     *
     * @param null|Queue $queue
     *
     * @return int
     */
    public function count(Queue $queue = null)
    {
        return $this->_count;
    }

    /**
     * Delete a message from the queue.
     *
     * Return true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param Message $message
     *
     * @return bool
     */
    public function deleteMessage(Message $message)
    {
        return $this->channel->basic_ack($message->amqpMessage->delivery_info['delivery_tag']);
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority()
    {
        throw new UnsupportedMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function showMessages($queueName)
    {
        throw new UnsupportedMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        throw new UnsupportedMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function logException($message, $e)
    {
        $this->logger->err($message->body);
        $this->logger->err($e->getMessage());
    }
}
