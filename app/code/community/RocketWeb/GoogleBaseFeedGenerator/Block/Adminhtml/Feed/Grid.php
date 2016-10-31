<?php

/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * init grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSort('id');
        $this->setId('googlebasefeedgenerator_feed_grid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setCollection(Mage::getModel('googlebasefeedgenerator/feed')->getCollection());
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * Prepare grid columns for feed list
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id',
            array(
                'header' => Mage::helper('googlebasefeedgenerator')->__('ID'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'id'
            )
        );

        $this->addColumn('name',
            array(
                'header' => Mage::helper('googlebasefeedgenerator')->__('Feed Name'),
                'index' => 'name',
                'width' => '180px'
            )
        );

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('cms')->__('Store View'),
                'index' => 'store_id',
                'type' => 'store',
                'store_all' => true,
                'store_view' => true,
                'sortable' => false,
                'filter_condition_callback' => array($this, '_filterStoreCondition'),
            ));
        }

        $this->addColumn('type',
            array(
                'header' => Mage::helper('googlebasefeedgenerator')->__('Feed Type'),
                'index' => 'type',
                'width' => '120px',
                'type' => 'options',
                'options' => Mage::getSingleton('googlebasefeedgenerator/feed_type')->getOptionArray(),
            )
        );

        $this->addColumn('schedule',
            array(
                'header' => Mage::helper('googlebasefeedgenerator')->__('Run Schedule'),
                'index' => 'schedule',
                'width' => '130px',
                'value' => '',
                'filter' => false,
                'sortable' => false,
                'frame_callback' => array($this, 'decorateSchedule')
            )
        );

        $this->addColumn('file',
            array(
                'header' => Mage::helper('googlebasefeedgenerator')->__('Feed File'),
                'index' => 'file',
                'sortable' => false,
                'filter' => false,
                'renderer' => 'googlebasefeedgenerator/adminhtml_feed_grid_column_file',
            )
        );

        $this->addColumn('status',
            array(
                'header' => Mage::helper('googlebasefeedgenerator')->__('Status'),
                'width' => '120px',
                'align' => 'left',
                'index' => 'status',
                'type' => 'options',
                'options' => RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::getStatusesOptions(),
                'frame_callback' => array($this, 'decorateStatus')
            )
        );

        $this->addColumn('actions_1',
            array(
                'header' => Mage::helper('googlebasefeedgenerator')->__('Actions'),
                'width' => '190px',
                'type' => 'action',
                'renderer' => 'googlebasefeedgenerator/adminhtml_feed_grid_column_actionbutton',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('googlebasefeedgenerator')->__('Test feed'),
                        'url' => array(
                            'base' => '*/*/test',
                        ),
                        'field' => 'id',
                        'popup' => true
                    ),
                    array(
                        'caption' => Mage::helper('googlebasefeedgenerator')->__('Run now'),
                        'url' => array(
                            'base' => '*/*/generate',
                            'params' => array('store' => $this->getRequest()->getParam('store')),
                        ),
                        'field' => 'id'
                    ),
                    array(
                        'caption' => Mage::helper('googlebasefeedgenerator')->__('View Log'),
                        'url' => array(
                            'base' => '*/*/viewlog',
                        ),
                        'field' => 'id',
                        'popup' => true
                    ),
                ),
                'filter' => false,
                'sortable' => false
            )
        );
        $this->addColumn('actions_2',
            array(
                'width' => '65px',
                'type' => 'action',
                'renderer' => 'googlebasefeedgenerator/adminhtml_feed_grid_column_actionbutton',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('googlebasefeedgenerator')->__('Configure'),
                        'url' => array(
                            'base' => '*/*/edit',
                            'params' => array('store' => $this->getRequest()->getParam('store'))
                        ),
                        'field' => 'id'
                    ),
                ),
                'filter' => false,
                'sortable' => false
            )
        );

        $this->addExportType('*/*/export', Mage::helper('sales')->__('Feed Configuration'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');

        $this->getMassactionBlock()->addItem('enable', array(
            'label' => Mage::helper('googlebasefeedgenerator')->__('Enable'),
            'url' => $this->getUrl('*/*/massEnable'),
        ));

        $this->getMassactionBlock()->addItem('disable', array(
            'label' => Mage::helper('googlebasefeedgenerator')->__('Disable'),
            'url' => $this->getUrl('*/*/massDisable'),
        ));

        $this->getMassactionBlock()->addItem('clone', array(
            'label' => Mage::helper('googlebasefeedgenerator')->__('Clone'),
            'url' => $this->getUrl('*/*/massClone'),
        ));

        $this->getMassactionBlock()->addItem('delete', array(

            'label' => Mage::helper('googlebasefeedgenerator')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('googlebasefeedgenerator')->__('Feed config(s) will be removed. Are you sure?')
        ));

        return $this;
    }

    /**
     * Return the edit URL
     *
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    /**
     * Callback from _prepareColumns()
     *
     * @param $collection
     * @param $column
     */
    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $this->getCollection()->addStoreFilter($value);
    }

    /**
     * Decorate status column values
     *
     * @param string $value
     * @param Mage_Index_Model_Process $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool $isExport
     *
     * @return string
     */
    public function decorateStatus($value, $row, $column, $isExport)
    {
        switch ($row->getData('status')) {
            case RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_COMPLETED :
                $class = 'grid-severity-notice';
                break;
            case RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_DISABLED :
            case RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_SCHEDULED :
                $class = 'grid-severity-minor';
                break;
            case RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_PENDING :
                $class = 'grid-severity-major';
                break;
            case RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_PROCESSING :
            case RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status::STATUS_ERROR :
                $class = 'grid-severity-critical';
                break;
            default:
                $class = '';
        }
        return '<span class="'.$class.'"><span>'.$row->getStatus()->getLabel().'</span></span>';
    }

    /**
     * Render calendar picker in schedule column
     *
     * @param string $value
     * @param Mage_Index_Model_Process $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool $isExport
     *
     * @return string
     */
    public function decorateSchedule($value, $row, $column, $isExport)
    {
        return $this->getScheduleGridCellBlock()->setFeed($row)
                                                ->toHtml();
    }

    protected function _prepareLayout()
    {
        $this->setChild('import_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Import'),
                    'onclick'   => 'document.location = \'' . $this->getUrl('*/*/import') . '\'',
                    'class'     => 'task'
                ))
        );
        return parent::_prepareLayout();
    }

    public function getImportButtonHtml()
    {
        return $this->getChildHtml('import_button');
    }

    public function getMainButtonsHtml()
    {
        $html = '<span><input type="checkbox" id="grid_autorefresh_check"';
        if (!isset($_COOKIE['grid_autorefresh_check']) || $_COOKIE['grid_autorefresh_check'] == 'true') {
            $html .= ' checked ';
        }
        $html .= '> Autorefresh</span> ';
        if($this->getFilterVisibility()){
            $html.= $this->getImportButtonHtml();
            $html.= $this->getResetFilterButtonHtml();
            $html.= $this->getSearchButtonHtml();
        }
        return $html;
    }

}
