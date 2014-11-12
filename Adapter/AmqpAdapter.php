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
use ZendQueue\Queue;

/**
 * Amqp adapter
 *
 * @see Zend_Queue_Adapter_AdapterAbstract
 */
class AmqpAdapter extends AbstractAdapter
{
    const DEFAULT_HOST          = '127.0.0.1';
    const DEFAULT_PORT          = 5672;
    const DEFAULT_VHOST         = '/';
    const DEFAULT_USERNAME      = 'guest';
    const DEFAULT_PASSWORD      = 'guest';

    /**
     * @var AMQPConnection  
     */
    protected $_connection;

    /**
     * @var AMQPChannel 
     */
    protected $_channel;

    /**
     * @var AMQPExchange 
     */
    protected $_exchange;

    /**
     * @var array 
     */
    protected $_defaultOptions = array(
        'driverOptions' => array(
            'attributes'    => array(
                'content_type'  => 'text/plain',
                'delivery_mode' => 2,
            ),
            'send_flags'    => AMQP_MANDATORY,   // AMQP_MANDATORY, AMQP_IMMEDIATE, AMQP_NOPARAM
            'receive_flags' => AMQP_AUTOACK,     // AMQP_AUTOACK
            'routing_key'   => '*',
            'channel'       => array(
                'prefetch_count' => 0,
                'prefetch_size'  => 0
            ),
            'exchange'      => array(
                'flags' => 0,                    // AMQP_PASSIVE  
                'name'  => 'queue_adapter_exchange',
                'type'  => AMQP_EX_TYPE_DIRECT   // AMQP_EX_TYPE_DIRECT, AMQP_EX_TYPE_FANOUT, AMQP_EX_TYPE_HEADER, AMQP_EX_TYPE_TOPIC
            ),
            'queue'         => array(
                'flags' => AMQP_DURABLE,         // AMQP_DURABLE, AMQP_PASSIVE, AMQP_EXCLUSIVE, AMQP_AUTODELETE
            ),
        )
    );

    public function __construct($options, Zend_Queue $queue = null)
    {
        if(!extension_loaded('amqp')) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Requires Amqp extension');
        }

        parent::__construct($options);
        
        require_once 'Zend/Config.php';
        $options = new Zend_Config($this->_defaultOptions, true);
        $options->merge(new Zend_Config($this->_options));
        $this->_options = $options->toArray();
        
        $options = &$this->_options['driverOptions'];

        foreach(array('host', 'port', 'vhost', 'username', 'password') as $option) {
            if(!array_key_exists($option, $options)) {
                $const = 'self::DEFAULT_' . strtoupper($option);

                if(defined($const)) {
                    $options[$option] = constant($const);
                }
            }
        }

        try {
            // init connection
            $this->_connection = new AMQPConnection(array(
                'host'     => $options['host'],
                'port'     => $options['port'],
                'vhost'    => $options['vhost'],
                'login'    => $options['username'],
                'password' => $options['password'],
            ));

            if (!$this->_connection->connect()) {
                require_once 'Zend/Queue/Exception.php';
                throw new Zend_Queue_Exception('Could not connection to queue');
            }

            // init channel
            $this->_channel = new AMQPChannel($this->_connection);
            $this->_channel->setPrefetchCount((int) $options['channel']['prefetch_count']);
            $this->_channel->setPrefetchSize((int) $options['channel']['prefetch_size']);

            // init exchange
            $this->_exchange = new AMQPExchange($this->_channel);
            $this->_exchange->setName($options['exchange']['name']);
            $this->_exchange->setType($options['exchange']['type']);
            $this->_exchange->setFlags((int) $options['exchange']['flags']);
            $this->_exchange->declare();
        }
        catch (AMQPConnectionException $e) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Lost connection with queue: ' . $e->getMessage());
        }
        catch (AMQPChannelException $e) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Queue channel is not open:' . $e->getMessage());
        }
        catch (AMQPExchangeException $e) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Queue amqp_channel is not connected to a broker: ' . $e->getMessage());
        }
        catch (Exception $e) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Unknown exception during queue creation: ' . $e->getMessage());
        }
    }
    
    protected function _getAmpqQueueByZendQueue(Zend_Queue $queue = null) 
    {
        if ($queue === null) {
            $queue = $this->_queue;
        }
        
        $adapter = $queue->getAdapter();
        if(!($adapter instanceof $this)) {
            require_once 'Zend/Queue/Exception.php';
            throw new Zend_Queue_Exception('Adapter must be instance ', get_class($this));
        }

        $queues = $adapter->_queues;
        if(!count($queues)) {
            return new AMQPQueue($adapter->_channel);
        }
        
        reset($queues);
        return current($queues);
    }
    
    /**
     * Encode message
     * 
     * @param mixed $message Message
     * @return string 
     */
    protected function _encodeMessage($message)
    {
        switch (gettype($message)) {
            case 'array':
            case 'object':
                $message = base64_encode(serialize($message));
            break;

            default:
                $message = (string) $message;
        }
        
        return $message;
    }
    
    /**
     * Decode message
     * 
     * @param string $message Message
     * @return mixed 
     */
    protected function _decodeMessage($message)
    {
        $decodeMessage = base64_decode($message);
        if($decodeMessage !== false) {
            $decodeMessage = @unserialize($decodeMessage);
            
            if($decodeMessage !== false) {
                return $decodeMessage;
            }
        }
        
        return $message;
    }

    public function beginTransaction()
    {
        $this->_channel->startTransaction();
    }

    public function commit()
    {
        $this->_channel->commitTransaction();
    }

    public function rollBack()
    {
        $this->_channel->rollbackTransaction();
    }

    /**
     * Does a queue already exist?
     *
     * @param  string $name
     * @return boolean
     */
    public function isExists($name)
    {
        return false;
    }

    /**
     * Create a new queue
     *
     * @param  string  $name Queue name
     * @param  integer $timeout Default visibility timeout
     * @return boolean
     */
    public function create($name, $timeout = null)
    {
        try {
            $options = &$this->_options['driverOptions'];

            $queue = new AMQPQueue($this->_channel);
            $queue->setName($name);
            $queue->setFlags((int) $options['queue']['flags']);
            $queue->declare();

            if(isset($options['routing_key']) && strlen($options['routing_key']) > 0) {
                $queue->bind($options['exchange']['name'], $options['routing_key']);
            }
            
            $this->_queues[$name] = $queue;
        }
        catch (Exception $e) {
            return false;
        }
        
        return true;
    }

    /**
     * Delete a queue and all of its messages
     *
     * @param  string $name Queue name
     * @return boolean
     */
    public function delete($name)
    {
        try {
            if(isset($this->_queues[$name])) {
                $queue = $this->_queues[$name];
            }
            else {
                $queue = new AMQPQueue($this->_channel);
                $queue->setName($name);
            }

            $queue->cancel();
            $queue->delete();
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }
    
    /**
     * Get an array of all available queues
     *
     * Not all adapters support getQueues(); use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @throws Zend_Queue_Exception (not supported)
     */
    public function getQueues()
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('getQueues() is not supported in this adapter');
    }
    
    /**
     * Return the approximate number of messages in the queue
     *
     * @param  Zend_Queue $queue
     * @return integer
     */
    public function count(Zend_Queue $queue = null)
    {
        return $this->_getAmpqQueueByZendQueue($queue)->declare();
    }
    
    /**
     * Send a message to the queue
     *
     * @param  mixed $message Message to send to the active queue
     * @param  Zend_Queue|null $queue
     * @return Zend_Queue_Message
     */
    public function send($message, Zend_Queue $queue = null)
    {
        if ($queue === null) {
            $queue = $this->_queue;
        }

        $message = $this->_encodeMessage($message);

        $this->_exchange->publish(
            $message,
            $this->_options['driverOptions']['routing_key'],
            $this->_options['driverOptions']['send_flags'],
            $this->_options['driverOptions']['attributes']
        );

        $classname = $queue->getMessageClass();
        if (!class_exists($classname)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($classname);
        }

        return new $classname(array(
            'queue' => $queue,
            'data'  => array(
                'message_id' => null,
                'handle'     => null,
                'body'       => $message,
                'md5'        => md5($message)
            )
        ));
    }
    
    /**
     * Return the first element in the queue
     *
     * @param  integer    $maxMessages
     * @param  integer    $timeout
     * @param  Zend_Queue $queue
     * @return Zend_Queue_Message_Iterator
     */
    public function receive($maxMessages = null, $timeout = null, Zend_Queue $queue = null)
    {
        if ($maxMessages === null) {
            $maxMessages = 1;
        }
        
        if ($queue === null) {
            $queue = $this->_queue;
        }

        $messages = array();
        
        if ($maxMessages > 0) {
            $amqpQueue = $this->_getAmpqQueueByZendQueue($queue);

            for ($i = 0; $i < $maxMessages; $i++) {
                $envelope = $amqpQueue->get($this->_options['driverOptions']['receive_flags']);

                if(!$envelope) {
                    break;
                }

                $messages[] = array(
                    'message_id' => $envelope->getMessageId(),
                    'handle'     => $envelope->getMessageId(),
                    'body'       => $this->_decodeMessage($envelope->getBody()),
                    'md5'        => md5($envelope->getBody())
                );
            }
        }

        $classname = $queue->getMessageSetClass();

        if (!class_exists($classname)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($classname);
        }
        return new $classname(array(
            'queue'        => $queue,
            'data'         => $messages,
            'messageClass' => $queue->getMessageClass()
        ));
        
        return Zend_Queue_Message_Iterator();
    }
    
    /**
     * Delete a message from the queue
     *
     * Returns true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param  Zend_Queue_Message $message
     * @return boolean
     */
    public function deleteMessage(Zend_Queue_Message $message)
    {
        require_once 'Zend/Queue/Exception.php';
        throw new Zend_Queue_Exception('deleteMessage() is not supported in this adapter');
    }
    
    /**
     * Return a list of queue capabilities functions
     *
     * $array['function name'] = true or false
     * true is supported, false is not supported.
     *
     * @param  string $name
     * @return array
     */
    public function getCapabilities()
    {
        return array(
            'create'        => true,
            'delete'        => true,
            'send'          => true,
            'receive'       => true,
            'deleteMessage' => false,
            'getQueues'     => false,
            'count'         => true,
            'isExists'      => false
        );
    }
}