<?php

namespace Heri\Bundle\JobQueueBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Heri\Bundle\JobQueueBundle\Entity\MessageLog
 *
 * @ORM\Table(name="queue_log")
 * @ORM\Entity
 */
class MessageLog
{
    /**
     * @var bigint $id
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var bigint $messageId
     *
     * @ORM\Column(name="message_id", type="bigint", nullable=false)
     */
    private $messageId;

    /**
     * @var datetime $dateLog
     *
     * @ORM\Column(name="date_log", type="datetime", nullable=false)
     */
    private $dateLog;

    /**
     * @var text $log
     *
     * @ORM\Column(name="log", type="text", nullable=false)
     */
    private $log;



    /**
     * Get id
     *
     * @return bigint 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set messageId
     *
     * @param bigint $messageId
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * Get messageId
     *
     * @return bigint 
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set dateLog
     *
     * @param datetime $dateLog
     */
    public function setDateLog($dateLog)
    {
        $this->dateLog = $dateLog;
    }

    /**
     * Get dateLog
     *
     * @return datetime 
     */
    public function getDateLog()
    {
        return $this->dateLog;
    }

    /**
     * Set log
     *
     * @param text $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    /**
     * Get log
     *
     * @return text 
     */
    public function getLog()
    {
        return $this->log;
    }
}
