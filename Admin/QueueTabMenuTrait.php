<?php

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
        $admin = $this->isChild() ? $this->getParent() : $this;

        if (!$childAdmin && !in_array($action, ['list'])) {
            return;
        }

        $admin = $this->isChild() ? $this->getParent() : $this;

        $id = $admin->getRequest()->get('id');

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
