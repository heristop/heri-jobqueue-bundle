<?php

/*
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
 * Class QueueLogAdmin.
 */
class QueueLogAdmin extends Admin
{
    use QueueTabMenuTrait;

    protected $baseRouteName = 'sonata_queue_log';
    protected $baseRoutePattern = '/queue_log';

    protected $translationDomain = 'HeriJobQueueBundle';

    protected $datagridValues = [
        '_sort_order' => 'DESC',
        '_sort_by' => 'id',
    ];

    protected $listModes = [
        'list' => [
            'class' => 'fa fa-list fa-fw',
        ],
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
            ->with('General', [])->end()
        ;

        $formMapper
            ->with('General')
            ->add('messageId')
            ->add('dateLog', 'sonata_type_datetime_picker', ['dp_side_by_side' => true])
            ->add('log')
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
            ->add('messageId')
            ->add('dateLog', 'doctrine_orm_datetime_range', ['field_type' => 'sonata_type_datetime_range_picker'])
            ->add('log', null, ['global_search' => false])
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
            ->add('messageId')
            ->add('dateLog')
            ->add('log')
            ->add('_action', 'actions', [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('messageId')
            ->add('dateLog')
            ->add('log')
        ;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->remove('create')
            ->remove('show')
        ;
    }
}
