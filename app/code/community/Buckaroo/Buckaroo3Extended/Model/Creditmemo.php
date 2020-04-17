<?php
class Buckaroo_Buckaroo3Extended_Model_Creditmemo extends Mage_Sales_Model_Order_Creditmemo
{
    public function getAllItems()
    {
        $refundType = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_afterpay/refundtype',
            Mage::app()->getStore()->getStoreId()
        );

        if (
            ($this->getOrder()->getPayment()->getMethod() == 'buckaroo3extended_afterpay')
            &&
            ($refundType == 'without')
        ) {
            return array();
        } else {
            return parent::getAllItems();
        }
    }
}