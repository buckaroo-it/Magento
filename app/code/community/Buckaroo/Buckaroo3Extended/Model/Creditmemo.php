<?php
class Buckaroo_Buckaroo3Extended_Model_Creditmemo extends Mage_Sales_Model_Order_Creditmemo
{
    public function refund()
    {
        Mage::helper('buckaroo3extended')->devLog(__METHOD__, 1);

        if (
            ($this->getOrder()->getPayment()->getMethod() == 'buckaroo3extended_afterpay20')
            &&
            Mage::getStoreConfig(
                'buckaroo/buckaroo3extended_afterpay20/custom_amount_capture',
                Mage::app()->getStore()->getStoreId()
            )
            &&
            ($postData = Mage::app()->getRequest()->getParam('creditmemo'))
            &&
            !empty($postData['adjustment_positive'])
        ) {
            Mage::helper('buckaroo3extended')->devLog(__METHOD__, 2);

            $this->setBaseAdjustmentPositive($postData['adjustment_positive']);
            $this->setAdjustmentPositive($postData['adjustment_positive']);
            $this->setGrandTotal($postData['adjustment_positive']);
            $this->setBaseGrandTotal($postData['adjustment_positive']);
            $this->save();
        }

        return parent::refund();

    }

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