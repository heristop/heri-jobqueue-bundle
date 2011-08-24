<?php

namespace Heri\JobQueueBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Heri\JobQueueBundle\Entity\Queue
 *
 * @ORM\Table(name="queue")
 * @ORM\Entity
 */
class Queue
{
    /**
     * @var integer $queueId
     *
     * @ORM\Column(name="queue_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $queueId;

    /**
     * @var string $queueName
     *
     * @ORM\Column(name="queue_name", type="string", length=100, nullable=false)
     */
    private $queueName;

    /**
     * @var smallint $timeout
     *
     * @ORM\Column(name="timeout", type="smallint", nullable=false)
     */
    private $timeout;



    /**
     * Get queueId
     *
     * @return integer 
     */
    public function getQueueId()
    {
        return $this->queueId;
    }

    /**
     * Set queueName
     *
     * @param string $queueName
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * Get queueName
     *
     * @return string 
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Set timeout
     *
     * @param smallint $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Get timeout
     *
     * @return smallint 
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
}