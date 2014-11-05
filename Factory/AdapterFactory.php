<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\Factory;

use Heri\Bundle\JobQueueBundle\Adapter\DoctrineAdapter;
use Heri\Bundle\JobQueueBundle\Exception\BadAdapterDefinitionException;

/**
 * This class is just a Adapter factory
 */
class AdapterFactory
{
    public static $em;
    
    /**
     * Create staticly desired Adapter
     *
     * @param string $type Type of Adapter to create
     *
     * @return LoggerInterface Adapter instance
     */
    static public function get($type)
    {
        $instance = null;

        switch ($type) {

            case 'doctrine':
                $instance = new DoctrineAdapter(array());
                $instance->em = self::$em;
                break;

            default:
                throw new BadAdapterDefinitionException;
        }

        return $instance;
    }
}