<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\Adapter;

use ZendQueue\Adapter\AbstractAdapter;
use ZendQueue\Exception;
use ZendQueue\Message;
use ZendQueue\Message\MessageIterator;
use ZendQueue\Queue;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

use Heri\Bundle\JobQueueBundle\Exception\AdapterRuntimeException;
use Heri\Bundle\JobQueueBundle\Exception\MissingConfigurationException;
use Heri\Bundle\JobQueueBundle\Exception\UnsupportedMethodCallException;

/**
 * Amqp adapter
 *
 * @see Zend_Queue_Adapter_AdapterAbstract
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
     * @var AMQPConnection
     */
    //private $_cnn = array();

    /**
     * @var AMQP_Queue_Exchange
     */
    //private $_exchange = null;

    /**
     * @var AMQPQueue
     */
    //private $_amqpQueue = null;

    /**
     * @var int AMQP queue flags
     */
    //private $_amqpQueueFlag = "AMQP_DURABLE";

    /**
     * @var int count of messages we got last time
     */
    private $_count;

    /**
     * Constructor
     *
     * @param array|Zend_Config $options options (host, port, login, password)
     * @param null|Zend_Queue   $queue
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
            throw new MissingConfigurationException("The options must be an associative array of host, port, login, password...");
        }
    }

    /**
     * Get AMQPConnection object
     * @return object
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get AMQPChannel object
     * @return object
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set exchange for sending message to queue
     * @param  string|AMQP_Queue_Exchange $exchange
     * @param  string                     $routingKey
     * @param  int                        $type       (AMQP_EX_TYPE_DIRECT, AMQP_EX_TYPE_FANOUT, AMQP_EX_TYPE_TOPIC or AMQP_EX_TYPE_HEADER)
     * @param  int                        $flags      (AMQP_PASSIVE, AMQP_DURABLE, AMQP_AUTODELETE)
     * @return AMQP_Queue_Exchange
     */
    /*public function setExchange($exchange, $routingKey = "*", $type = AMQP_EX_TYPE_DIRECT, $flags = AMQP_DURABLE)
    {
        if (! $exchange instanceof AMQP_Queue_Exchange) {
            $exchange = new AMQP_Queue_Exchange($this->_cnn, $exchange, $type, $flags);
        }
        $this->_exchange = $exchange;
        $this->setRoutingKey($routingKey);

        return $exchange;
    }*/

    /**
     * Set routing key for queu
     * @param  string     $routingKey
     * @param  AMQP_Queue $queue
     * @return bool
     */
    /*public function setRoutingKey($routingKey, AMQP_Queue $queue = null)
    {
        if ($queue) {
            $queueName = $queue->getName();
        } else {
            $queueName = $this->_queue->getName();
        }

        return $this->_exchange->bind($queueName, $routingKey);
    }*/

    /**
     * set AMQPQueue flag(s)
     * @param int $flag
     */
    /*public function setQueueFlag($flag)
    {
        $this->_amqpQueueFlag = $flag;
    }*/

    /**
     * create queue
     * @param  string $name
     * @param  int    $timeout
     * @return int
     */
    public function create($name, $timeout = null)
    {
        try {
            //$this->_count = $this->_amqpQueue->declare($name, $this->_amqpQueueFlag);

            //$this->channel->queue_delete($name);

            /*
                name: $queue
                passive: false
                durable: true // the queue will survive server restarts
                exclusive: false // the queue can be accessed in other channels
                auto_delete: false //the queue won't be deleted once the channel is closed.
            */
            $this->channel->queue_declare($name, false, true, false, false);

            //die("count: ".var_dump($this->_count));

        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * delete queue
     * @param $name
     * @return bool
     */
    public function delete($name)
    {
        //$this->channel->exchange_delete($this->exchangeName);
        $this->channel->queue_delete($name);

        return true;
    }

    /**
     * Publish message to queue
     * @param  mixed      $message (array or string)
     * @param  Zend_Queue $queue
     * @return boolean
     */
    public function send($message, Queue $queue = null)
    {
        if ($queue === null) {
            $queue = $this->_queue;
        }

        if (is_array($message)) {
            $message = \Zend\Json\Encoder::encode($message);
        }

        /*if ($queue) {
            $routingKey = $queue->getOption('routingKey');
        } else {
            $routingKey = $this->_queue->getOption('routingKey');
        }*/

        // todo
        /*if ($this->_exchange) {
            return $this->_exchange->publish($message, $routingKey, AMQP_MANDATORY, array('delivery_mode' => 2));
        } else {
            throw new AdapterRuntimeException("Rabbitmq exchange not found");
        }*/

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

        //$this->getChannel()->basic_publish($amq, $this->exchange, $this->key);

        $amqpMessage = new AMQPMessage($message, array(
            'content_type' => 'text/plain',
            'delivery_mode' => 2 
        ));

        $this->channel->basic_publish($amqpMessage, $this->exchangeName);

        //$this->channel->close();
        //$this->connection->close();
    }

    /**
     * Get messages in the queue
     *
     * @param  integer|null                $maxMessages Maximum number of messages to return
     * @param  integer|null                $timeout     Visibility timeout for these messages
     * @param  Zend_Queue|null             $queue
     * @return Zend_Queue_Message_Iterator
     */
    public function receive($maxMessages = null, $timeout = null, Queue $queue = null)
    {
        $result = array();

        if ($queue === null) {
            $queue = $this->_queue;
        }

        $maxMessages = (int) $maxMessages ? (int) $maxMessages : 1;
        if (isset($this->_options['method']) && 'consume' == $this->_options['method']) {
            // use new AmqpAdapter(array('method' => 'consume')) to use CONSUME approach
            /*$consumeOptions = array(
                'min' => 1,
                'max' => $maxMessages,
                'ack' => false,
            );
            $result = $this->_amqpQueue->consume($consumeOptions);
            $this->_count -= sizeof($result);*/
        } else {
            // default approach is GET
            
            for ($i = $maxMessages; $i > 0; $i--) {


        
/*$callback = function($amqpMessage) {

  echo " [x] Received ", $amqpMessage->body, "\n";

sleep(60);

  echo " [x] Done", "\n";
  $amqpMessage->delivery_info['channel']->basic_ack($amqpMessage->delivery_info['delivery_tag']);

};

$this->channel->basic_qos(null, 1, null);
$this->channel->basic_consume($queue->getName(), '', false, false, false, false, $callback);*/


     /* $this->channel->basic_consume(
            $queue->getName(),
            'jobqueue_'.uniqid(),
            false,
            false,
            false,
            false,
            array($this, 'receiveMessage'
        ));*/



                $amqpMessage = $this->channel->basic_get($queue->getName());

                //var_dump($amqpMessage);

                if (isset($amqpMessage->delivery_info['delivery_tag'])) {
                    $result[] = array(
                        'body' => $amqpMessage->body,
                        'amqpMessage' => $amqpMessage
                    );   
                    $this->_count = $amqpMessage->delivery_info['message_count'];
                }


                //$amqpMessage->delivery_info['channel']->basic_ack($amqpMessage->delivery_info['delivery_tag']);
                /*$message = $this->_amqpQueue->get();
                if (isset($message['delivery_tag'])) {
                    $result[] = $message;
                    $this->_count = $message['count'];
                }
                if ($message['count'] <= 0) {
                    break;
                }*/
            }
        }

        $options = array(
            'queue'        => $queue,
            'data'         => $result,
            'messageClass' => $queue->getMessageClass(),
        );

        $classname = $queue->getMessageSetClass();

        return new $classname($options);

        //return new MessageIterator(array('data' => $result));
    }

    public function getCapabilities()
    {
        return array(
            'create' => true,
            'delete' => true,
            'send' => true,
            'count' => true,
            'deleteMessage' => true,
        );
    }

    /**
     * Does a queue already exist?
     *
     * Use isSupported('isExists') to determine if an adapter can test for
     * queue existance.
     *
     * @param  string  $name Queue name
     * @return boolean
     */
    public function isExists($name)
    {
        return isset($this->_count);
    }

    /**
     * Get an array of all available queues
     *
     * Not all adapters support getQueues(); use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @return array
     */
    public function getQueues()
    {
        return array($this->_queue);
    }

    /**
     * Return the approximate number of messages in the queue
     *
     * @param  Zend_Queue|null $queue
     * @return integer
     */
    public function count(Queue $queue = null)
    {
        return $this->_count;
    }

    /**
     * Delete a message from the queue
     *
     * Return true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  Zend_Queue_Message $message
     * @return boolean
     */
    public function deleteMessage(Message $message)
    {
        return $this->channel->basic_ack($message->amqpMessage->delivery_info['delivery_tag']);
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
        error_log($message->body.' '.$e->getMessage(), 3, 'debug.txt');
    }

}
