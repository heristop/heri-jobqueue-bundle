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
 * Class QueueAdmin.
 */
class QueueAdmin extends Admin
{
    use QueueTabMenuTrait;

    protected $baseRouteName = 'sonata_queue';
    protected $baseRoutePattern = '/queue';

    protected $translationDomain = 'HeriJobQueueBundle';

    protected $datagridValues = [
        '_sort_order' => 'ASC',
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
            ->with('General', ['class' => 'col-md-12'])->end()
        ;

        $formMapper
            ->with('General')
                ->add('name')
                ->add('timeout')
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
            ->add('id')
            ->add('name', null, ['global_search' => false])
            ->add('timeout')
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
            ->add('name', null, ['editable' => false])
            ->add('timeout', null, ['editable' => true])
            ->add('_action', 'actions', [
                'actions' => [
                    'showMessages' => [
                        'template' => 'TaladJobSystemBundle:QueueAdmin:showMessages.html.twig',
                    ],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('name')
            ->add('timeout')
        ;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('showMessages', $this->getRouterIdParameter().'/list');
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchActions()
    {
        // disable batch action
    }
}
