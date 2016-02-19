<?php

namespace Heri\Bundle\JobQueueBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Heri\Bundle\JobQueueBundle\Entity\Queue.
 *
 * @ORM\Table(name="queue")
 * @ORM\Entity
 */
class Queue
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var smallint
     *
     * @ORM\Column(name="timeout", type="smallint", nullable=false)
     */
    private $timeout;

    /**
     * @var int
     *
     * @ORM\Column(name="max_retries", type="integer", nullable=true)
     */
    private $maxRetries = null;

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set timeout.
     *
     * @param smallint $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Get timeout.
     *
     * @return smallint
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return int
     */
    public function getMaxRetries()
    {
        return $this->maxRetries;
    }

    /**
     * @param int $maxRetries
     */
    public function setMaxRetries($maxRetries)
    {
        $this->maxRetries = $maxRetries;
    }
}
