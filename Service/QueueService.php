<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\JobQueueBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

use Heri\JobQueueBundle\Command\QueueCommand;
use Heri\JobQueueBundle\Entity\Message;
use Heri\JobQueueBundle\Entity\MessageLog;

class QueueService
{
    protected
        $em,
        $logger,
        $config,
        $output,
        $queue
    ;

    public function __construct(Logger $logger, EntityManager $em = null)
    {
        $this->logger = $logger;
        $this->em = $em;
    }
    
    public function configure($name, EntityManager $em)
    {
        $this->em = $em;
        $connection = $em->getConnection();
        
        $this->config = array(
          'name' => $name,
          'driverOptions' => array(
            'host'        => $connection->getHost(),
            'port'        => $connection->getPort(),
            'username'    => $connection->getUsername(),
            'password'    => $connection->getPassword(),
            'dbname'      => $connection->getDatabase(),
            'persistent'  => true,
            'type'        => 'pdo_mysql'
          )
        );
        
        $this->queue = new \Zend_Queue('Db', $this->config); 
        $this->queue->createQueue($name);
    }
    
    public function receive($maxMessages, QueueCommand $command, OutputInterface $output)
    {
        $this->output = $output;
        $this->application = $command->getApplication();
        
        $messages = $this->queue->receive($maxMessages);
        
        if ($messages && $messages->count() > 0) {
            $this->execute($messages);
        }
    }
    
    /**
     * @param array $args
     */
    public function sync($args)
    {
        if (!is_null($this->queue)) {
          $this->queue->send(serialize($args));
        }
    }
    
    public function getEntityManager()
    {
        return $this->em;
    }
    
    public function getLogger()
    {
        return $this->logger;
    }
    
    protected function execute($messages)
    {
        foreach ($messages as $message)
        {
            $output = date('H:i:s') . '-' . ($message->failed ? 'failed' : 'new');
            $output .= '['.$message->message_id.']';
            
            $this->output->writeLn('<comment>' . $output . '</comment>');
            
            $args = unserialize($message->body);
            try {
                $input = new ArrayInput(array_merge(array(''), $args['arguments']));
                $command = $this->application->find($args['command']);
                $returnCode = $command->run($input, $this->output);
                
                $this->queue->deleteMessage($message);

                $this->output->writeLn('<comment>  > </comment><info>Ended</info>');
            }
            catch (\Exception $e) {

                $this->em->createQuery('UPDATE Heri\JobQueueBundle\Entity\Message m SET m.ended = 0, m.failed = 1 WHERE m.messageId = ?1')
                    ->setParameter(1, $message->message_id)
                    ->execute();
                    
                $log = new MessageLog();
                $log->setMessageId($message->message_id);
                $log->setDateLog(new \DateTime("now"));
                $log->setLog($e->getMessage());
                $this->em->persist($log);
                $this->em->flush();
                
                $this->output->writeLn('<comment>  > </comment><error>Failed</error> ' . $e->getMessage());
            }
        }
    }
}