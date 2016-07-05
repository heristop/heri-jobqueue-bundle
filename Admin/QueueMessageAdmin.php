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

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Admin\Admin;

/**
 * Class QueueMessageAdmin.
 */
class QueueMessageAdmin extends Admin
{
    use QueueTabMenuTrait;

    protected $baseRouteName = 'sonata_queue_message';
    protected $baseRoutePattern = '/queue_message';

    protected $translationDomain = 'HeriJobQueueBundle';

    protected $datagridValues = [
        '_sort_order' => 'ASC',
        '_sort_by' => 'id',
    ];

    /**
     * Fields to be shown on create/edit forms.
     *
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        // define group zoning
        $formMapper
            ->with('General', ['class' => 'col-md-8'])->end()
            ->with('Status', ['class' => 'col-md-4'])->end()
        ;

        $formMapper
            ->with('General')
                ->add('queue')
                ->add('handle')
                ->add('body')
                ->add('priority')
            ->end()
            ->with('Status')
                ->add('failed', null, ['required' => false])
                ->add('ended', null, ['required' => false])
            ->end()
        ;
    }

    /**
     * Fields to be shown on filter forms.
     *
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('queue')
            ->add('body', null, ['global_search' => false])
            ->add('priority')
            ->add('failed')
            ->add('ended')
        ;
    }

    /**
     * Fields to be shown on lists.
     *
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('queue')
            ->add('body')
            ->add('handle')
            ->add('priority', null, ['editable' => true])
            ->add('failed', null, ['editable' => true])
            ->add('ended', null, ['editable' => true])
            ->add('_action', 'actions', [
                'actions' => [
                    'showLogs' => [
                        'template' => 'HeriJobQueueBundle:QueueMessageAdmin:showLogs.html.twig',
                    ],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('queue')
            ->add('handle')
            ->add('body')
            ->add('failed')
            ->add('ended')
        ;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('showLogs', $this->getRouterIdParameter().'/list');
    }
}
