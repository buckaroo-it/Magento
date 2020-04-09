<?php
class Buckaroo_Buckaroo3Extended_Model_Creditmemo extends Mage_Sales_Model_Order_Creditmemo
{
    public function getAllItems()
    {
        $refundType = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_afterpay/refundtype',
            Mage::app()->getStore()->getStoreId()
        );

        if ($refundType == 'without') {
            return array();
        } else {
            return parent::getAllItems();
        }
    }
}