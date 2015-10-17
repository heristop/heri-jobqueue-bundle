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
use Heri\Bundle\JobQueueBundle\Exception\AdapterRuntimeException;

/**
 * Doctrine adapter.
 *
 * @see ZendQueue\Adapter\AbstractAdapter
 */
class DoctrineAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * var Doctrine\ORM\EntityManager.
     */
    public $em;

    /**
     * var int.
     */
    public $priority = 0;

    /**
     * Does a queue already exist?
     *
     * Throws an exception if the adapter cannot determine if a queue exists.
     * use isSupported('isExists') to determine if an adapter can test for
     * queue existance.
     *
     * @param string $name
     *
     * @return bool
     *
     * @throws ZendQueue\Exception
     */
    public function isExists($name)
    {
        $repo = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Queue')
            ->findOneBy([
                'name' => $name,
            ]);

        return ($repo) ? true : false;
    }

    /**
     * Create a new queue.
     *
     * Visibility timeout is how long a message is left in the queue "invisible"
     * to other readers.  If the message is acknowleged (deleted) before the
     * timeout, then the message is deleted.  However, if the timeout expires
     * then the message will be made available to other queue readers.
     *
     * @param string $name    Queue name
     * @param int    $timeout Default visibility timeout
     *
     * @return bool
     *
     * @throws ZendQueue\Exception - database error
     */
    public function create($name, $timeout = null)
    {
        if ($this->isExists($name)) {
            return false;
        }

        $queue = new \Heri\Bundle\JobQueueBundle\Entity\Queue();
        $queue->setName($name);
        $newtimeout = ($timeout === null) ? self::CREATE_TIMEOUT_DEFAULT : (int) $timeout;
        $queue->setTimeout($newtimeout);

        $this->em->persist($queue);
        $this->em->flush();

        return true;
    }

    /**
     * Delete a queue and all of it's messages.
     *
     * Returns false if the queue is not found, true if the queue exists
     *
     * @param string $name Queue name
     *
     * @return bool
     *
     * @throws ZendQueue\Exception
     */
    public function delete($name)
    {
        // Get primary key
        $id = $this->getQueueEntity($name);

        $queue = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Queue')
            ->find($id);

        $messages = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Message')
            ->findBy([
                'queue' => $queue,
            ]);
        foreach ($messages as $message) {
            $this->em->remove($message);
        }

        $this->em->remove($queue);
        $this->em->flush();
        $this->em->clear();

        return true;
    }

    /*
     * Get an array of all available queues
     *
     * Not all adapters support getQueues(), use isSupported('getQueues')
     * to determine if the adapter supports this feature.
     *
     * @return array
     */
    public function getQueues()
    {
        $list = [];

        $queues = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Queue')
            ->findAll();
        foreach ($queues as $queue) {
            $list[] = $queue->name;
        }

        return $list;
    }

    /**
     * Return the approximate number of messages in the queue.
     *
     * @param ZendQueue\Queue $queue
     *
     * @return int
     *
     * @throws ZendQueue\Exception
     */
    public function count(Queue $queue = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('count(m)')
            ->from('Heri\Bundle\JobQueueBundle\Entity\Message', 'm')
            ->leftJoin('m.queue', 'Queue')
        ;

        if ($queue instanceof Queue) {
            $qb
                ->where($qb->expr()->eq('Queue.name', ':name'))
                ->setParameter('name', $queue->getName())
            ;
        }

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * Send a message to the queue.
     *
     * @param string          $message Message to send to the active queue
     * @param ZendQueue\Queue $queue
     *
     * @return ZendQueue\Message
     *
     * @throws ZendQueue\Exception
     */
    public function send($message, Queue $queue = null)
    {
        $body = '';

        if ($queue === null) {
            $queue = $this->_queue;
        }

        if (is_scalar($message)) {
            $body = (string) $message;
        }

        if (is_string($message)) {
            $body = trim($message);
        }

        if (!$this->isExists($queue->getName())) {
            throw new AdapterRuntimeException("Queue does not exist: {$queue->getName()}");
        }

        $entity = $this->createMessage($queue, $body);

        $options = [
            'queue' => $queue,
            'data' => $entity->toArray(),
        ];

        $classname = $queue->getMessageClass();

        return new $classname($options);
    }

    /**
     * Get messages in the queue.
     *
     * @param int             $maxMessages Maximum number of messages to return
     * @param int             $timeout     Visibility timeout for these messages
     * @param ZendQueue\Queue $queue
     *
     * @return ZendQueue\MessageIterator
     *
     * @throws ZendQueue\Exception Database error
     */
    public function receive($maxMessages = null, $timeout = null, Queue $queue = null)
    {
        $result = [];

        // Cache microtime
        $microtime = microtime(true);

        if ($queue === null) {
            $queue = $this->_queue;
        }

        if ($maxMessages > 0) {
            $messages = $this->getMessages(
                $maxMessages,
                $timeout,
                $queue,
                $microtime
            );

            // Update working messages
            foreach ($messages as $message) {
                $key = md5(uniqid(rand(), true));
                $message->setHandle($key);
                $message->setTimeout($microtime);

                $result[] = $message->toArray();
            }
            $this->em->flush();
        }

        $options = [
            'queue' => $queue,
            'data' => $result,
            'messageClass' => $queue->getMessageClass(),
        ];

        $classname = $queue->getMessageSetClass();

        return new $classname($options);
    }

    /**
     * Delete a message from the queue.
     *
     * Returns true if the message is deleted, false if the deletion is
     * unsuccessful.
     *
     * @param ZendQueue\Message $message
     *
     * @return bool
     *
     * @throws ZendQueue\Exception - database error
     */
    public function deleteMessage(Message $message)
    {
        $repo = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Message')
            ->findOneBy([
                'handle' => $message->handle,
            ]);

        $this->em->remove($repo);
        $this->em->flush();

        return $this->em->clear();
    }

    /**
     * Return a list of queue capabilities functions.
     *
     * $array['function name'] = true or false
     * true is supported, false is not supported.
     *
     * @param string $name
     *
     * @return array
     */
    public function getCapabilities()
    {
        return [
            'create' => true,
            'delete' => true,
            'send' => true,
            'receive' => true,
            'deleteMessage' => true,
            'getQueues' => true,
            'count' => true,
            'isExists' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * {@inheritdoc}
     */
    public function showMessages($queueName)
    {
        $results = [];
        if ($this->isExists($queueName)) {
            $qb = $this->em->createQueryBuilder();
            $qb
                ->select('m.id, m.body, m.created_at, m.ended, m.failed')
                ->from('Heri\Bundle\JobQueueBundle\Entity\Message', 'm')
                ->leftJoin('m.queue', 'Queue')
                ->where($qb->expr()->eq('Queue.name', ':name'))
                ->setParameter('name', $queueName)
            ;

            $query = $qb->getQuery();
            $results = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return $this->em->createQuery(
            'DELETE Heri\Bundle\JobQueueBundle\Entity\MessageLog ml'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function logException($message, $e)
    {
        $query = $this->em->createQuery(
            'SELECT COUNT(ml.id) FROM Heri\Bundle\JobQueueBundle\Entity\MessageLog ml '.
            'WHERE ml.id = ?1'
        )
        ->setParameter(1, $message->id);
        $count = $query->getSingleScalarResult();

        if ($count == 0) {
            $this->em
                ->createQuery(
                    'UPDATE Heri\Bundle\JobQueueBundle\Entity\Message m '.
                    'SET m.ended = 0, m.failed = 1, m.priority = 0 '.
                    'WHERE m.id = ?1'
                )
                ->setParameter(1, $message->id)
                ->execute();

            $log = new \Heri\Bundle\JobQueueBundle\Entity\MessageLog();
            $log->setMessageId($message->id);
            $log->setDateLog(new \DateTime('now'));
            $log->setLog($e->getMessage());
            $this->em->persist($log);
            $this->em->flush();
        }
    }

    /**
     * Create a new message.
     *
     * @param ZendQueue\Queue $queue
     * @param string          $body
     */
    protected function createMessage(Queue $queue, $body)
    {
        // check if message exist
        $message = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Message')
            ->findOneBy([
                'md5' => md5($body),
            ]);

        if (!$message) {
            $message = new \Heri\Bundle\JobQueueBundle\Entity\Message();
            $message->setQueue($this->getQueueEntity($queue->getName()));
            $message->setBody($body);
            $message->setMd5(md5($body));
            $message->setPriority($this->priority);
            $message->setFailed(false);
            $message->setEnded(false);

            $this->em->persist($message);
            $this->em->flush();
            $this->em->clear();
        }

        return $message;
    }

    /**
     * Get messages of the queue.
     *
     * @param int             $maxMessages
     * @param int             $timeout
     * @param ZendQueue\Queue $queue
     * @param int             $microtime
     */
    protected function getMessages($maxMessages, $timeout, $queue = null, $microtime = null)
    {
        if ($maxMessages === null) {
            $maxMessages = 1;
        }

        if ($timeout === null) {
            $timeout = self::RECEIVE_TIMEOUT_DEFAULT;
        }

        $andWhere = '';
        if ($queue instanceof Queue) {
            $andWhere = 'AND (m.queue = :queue)';
        }

        // Search for all messages inside the timeout
        $sql = 'SELECT m '.
            'FROM Heri\Bundle\JobQueueBundle\Entity\Message m '.
            'LEFT JOIN m.queue q '.
            'WHERE (m.handle IS NULL OR m.handle = \'\' OR m.timeout + '.
            (int) $timeout.' < '.(int) $microtime.') '.$andWhere.' '.
            'ORDER BY m.priority DESC';

        $query = $this->em->createQuery($sql);

        if ($queue instanceof Queue) {
            $query->setParameter('queue', $this->getQueueEntity($queue->getName()));
        }
        $query->setMaxResults($maxMessages);

        return $query->getResult();
    }

    /**
     * Get the queue entity.
     *
     * @param string $name
     *
     * @return Queue Entity
     *
     * @throws ZendQueue\Exception
     */
    protected function getQueueEntity($name)
    {
        $repo = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Queue')
            ->findOneBy([
                'name' => $name,
            ]);

        if (!$repo) {
            throw new AdapterRuntimeException("Queue does not exist: {$name}");
        }

        return $repo;
    }
}
