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
use Heri\JobQueueBundle\Adapter\DoctrineAdapter;

class QueueService
{
    protected
        $em,
        $logger,
        $config,
        $output,
        $queue,
        $adapter
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
        );
        
        $this->adapter = new DoctrineAdapter(array());
        $this->adapter->setEm($em);
        
        $this->queue = new \ZendQueue\Queue($this->adapter, $this->config);
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
            $output = date('H:i:s') . ' - ' . ($message->failed ? 'failed' : 'new');
            $output .= '['.$message->id.']';
            
            $this->output->writeLn('<comment>' . $output . '</comment>');
            
            $args = unserialize($message->body);
            try {
                $input = new ArrayInput(array_merge(array(''), $args['argument']));
                $command = $this->application->find($args['command']);
                $returnCode = $command->run($input, $this->output);
                
                $this->queue->deleteMessage($message);

                $this->output->writeLn('<info>Ended</info>');
            }
            catch (\Exception $e) {
                $this->adapter->logException($message, $e);
                $this->output->writeLn('<error>Failed</error> ' . $e->getMessage());
            }
        }
    }
}