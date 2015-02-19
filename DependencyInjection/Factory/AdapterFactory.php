<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\DependencyInjection\Factory;

use Heri\Bundle\JobQueueBundle\Adapter as Adapter;
use Heri\Bundle\JobQueueBundle\Exception\BadAdapterDefinitionException;

/**
 * Adapter factory
 */
class AdapterFactory
{
    public static $em;

    /**
     * Create staticly desired Adapter
     *
     * @param string $type Type of Adapter to create
     *
     * @return AdapterInterface Adapter instance
     */
    public static function get($type)
    {
        $instance = null;
        $options = array();

        switch ($type) {

            case 'doctrine':
                $instance = new Adapter\DoctrineAdapter($options);
                $instance->em = self::$em;
                break;

            case 'amqp':
                $options = array(
                    'host' => 'localhost',
                    'port' => '5672',
                    'user' => 'guest',
                    'password' => 'guest'
                );

                $instance = new Adapter\AmqpAdapter($options);
                break;

            default:
                throw new BadAdapterDefinitionException();
        }

        return $instance;
    }
}
