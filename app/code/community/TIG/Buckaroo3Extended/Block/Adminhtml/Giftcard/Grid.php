<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */
class TIG_Buckaroo3Extended_Block_Adminhtml_Giftcard_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setSaveParametersInSession(true);
        $this->setId('giftcard_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('desc');
    }

    /**
     * Returns collection for grid. Collection has not yet been filtered
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('buckaroo3extended/giftcard_collection');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $helper  = Mage::helper('buckaroo3extended');
        $storeId = $this->getStoreId();

        $this->addColumn(
            'entity_id',
            array(
                'header' => $helper->__('ID'),
                'align'  => 'right',
                'width'  => '50px',
                'index'  => 'entity_id',
                'type'   => 'number',
            )
        );

        $this->addColumn(
            'servicecode',
            array(
                'header' => $helper->__('Service Code'),
                'align'  => 'left',
                'index'  => 'servicecode',
            )
        );

        $this->addColumn(
            'label',
            array(
                'header' => $helper->__('Name'),
                'align'  => 'left',
                'index'  => 'label',
            )
        );

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('entity_id' => $row->getId()));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
