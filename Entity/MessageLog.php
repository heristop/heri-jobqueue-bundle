<?php

namespace Heri\Bundle\JobQueueBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Heri\Bundle\JobQueueBundle\Entity\MessageLog.
 *
 * @ORM\Table(name="queue_log")
 * @ORM\Entity
 */
class MessageLog
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
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Message", cascade="remove")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", nullable=true)
     */
    private $messageId;

    /**
     * @var datetime
     *
     * @ORM\Column(name="date_log", type="datetime", nullable=false)
     */
    private $dateLog;

    /**
     * @var text
     *
     * @ORM\Column(name="log", type="text", nullable=false)
     */
    private $log;

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
     * Set messageId.
     *
     * @param int $messageId
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * Get messageId.
     *
     * @return int
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set dateLog.
     *
     * @param datetime $dateLog
     */
    public function setDateLog($dateLog)
    {
        $this->dateLog = $dateLog;
    }

    /**
     * Get dateLog.
     *
     * @return datetime
     */
    public function getDateLog()
    {
        return $this->dateLog;
    }

    /**
     * Set log.
     *
     * @param text $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    /**
     * Get log.
     *
     * @return text
     */
    public function getLog()
    {
        return $this->log;
    }
}
