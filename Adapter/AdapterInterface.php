<?php

/**
 * This file is part of HeriJobQueueBundle.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\Adapter;

interface AdapterInterface
{
    /**
     * Show messages.
     *
     * @param string $queueName
     *
     * @return array
     */
    public function showMessages($queueName);

    /**
     * Flush message log.
     *
     * @return bool
     */
    public function flush();

    /**
     * Insert exception in message log.
     *
     * @param string              $message
     * @param ZendQueue\Exception $e
     */
    public function logException($message, $e);
}
