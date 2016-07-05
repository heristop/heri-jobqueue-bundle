<?php

/**
 * This file is part of HeriJobQueueBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\Admin;

use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Admin\AdminInterface;

trait QueueTabMenuTrait
{
    /**
     * {@inheritdoc}
     */
    protected function configureTabMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        if (!$childAdmin && !in_array($action, ['list'])) {
            return;
        }

        $menu->addChild('link_queue_list', [
            'label' => 'Queues',
            'attributes' => ['class' => 'btn', 'icon' => 'fa fa-tasks'],
            'route' => 'sonata_queue_list',
        ]);

        $menu->addChild('link_queue_message_list', [
            'label' => 'Messages',
            'attributes' => ['class' => 'btn', 'icon' => 'fa fa-send'],
            'route' => 'sonata_queue_message_list',
        ]);

        $menu->addChild('link_queue_log_list', [
            'label' => 'Exceptions',
            'attributes' => ['class' => 'btn', 'icon' => 'fa fa-terminal'],
            'route' => 'sonata_queue_log_list',
        ]);
    }
}
