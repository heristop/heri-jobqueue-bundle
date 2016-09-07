<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Heri\Bundle\JobQueueBundle\Entity\Message.
 *
 * @ORM\Table(name="queue_message")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Message
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Queue")
     */
    private $queue;

    /**
     * @var string
     *
     * @ORM\Column(name="handle", type="string", length=32, nullable=true)
     */
    private $handle;

    /**
     * @var text
     *
     * @ORM\Column(name="body", type="text", nullable=false)
     */
    private $body;

    /**
     * @var string
     *
     * @ORM\Column(name="md5", type="string", length=32, nullable=false)
     */
    private $md5;

    /**
     * @var decimal
     *
     * @ORM\Column(name="timeout", type="decimal", nullable=true)
     */
    private $timeout;

    /**
     * @var int
     *
     * @ORM\Column(name="created", type="integer", nullable=false)
     */
    private $created;

    /**
     * @var smallint
     *
     * @ORM\Column(type = "smallint")
     */
    private $priority = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="failed", type="boolean", nullable=false)
     */
    private $failed;

    /**
     * @var int
     *
     * @ORM\Column(name="num_retries", type="integer", nullable=false)
     */
    private $numRetries = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="ended", type="boolean", nullable=false)
     */
    private $ended;

    /** @ORM\PrePersist */
    public function prePersist()
    {
        $this->md5 = md5($this->body);
        $this->created = time();
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * Get messageId.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set handle.
     *
     * @param string $handle
     */
    public function setHandle($handle)
    {
        $this->handle = $handle;
    }

    /**
     * Get handle.
     *
     * @return string
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Set body.
     *
     * @param text $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Get body.
     *
     * @return text
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set md5.
     *
     * @param string $md5
     */
    public function setMd5($md5)
    {
        $this->md5 = $md5;
    }

    /**
     * Get md5.
     *
     * @return string
     */
    public function getMd5()
    {
        return $this->md5;
    }

    /**
     * Set timeout.
     *
     * @param decimal $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Get timeout.
     *
     * @return decimal
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set created.
     *
     * @param int $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set priority.
     *
     * @param smallint $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Get pririty.
     *
     * @return pririty
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set failed.
     *
     * @param bool $failed
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;
    }

    /**
     * Get failed.
     *
     * @return bool
     */
    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * Set ended.
     *
     * @param bool $ended
     */
    public function setEnded($ended)
    {
        $this->ended = $ended;
    }

    /**
     * Get ended.
     *
     * @return bool
     */
    public function getEnded()
    {
        return $this->ended;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Set queue.
     *
     * @param \Heri\Bundle\JobQueueBundle\Entity\Queue $queue
     *
     * @return Message
     */
    public function setQueue(\Heri\Bundle\JobQueueBundle\Entity\Queue $queue = null)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Get queue.
     *
     * @return \Heri\Bundle\JobQueueBundle\Entity\Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @return int
     */
    public function getNumRetries()
    {
        return $this->numRetries;
    }

    /**
     * @param int $numRetries
     */
    public function setNumRetries($numRetries)
    {
        $this->numRetries = $numRetries;
    }
}
