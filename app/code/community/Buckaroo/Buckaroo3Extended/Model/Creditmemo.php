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

            $adjustment = is_scalar($postData['adjustment_positive'])
                ? str_replace(',', '.', trim((string) $postData['adjustment_positive']))
                : '';

            if (!is_numeric($adjustment) || $adjustment <= 0) {
                Mage::throwException(Mage::helper('sales')->__('The credit memo amount should be a positive number.'));
            }

            //the amount is entered in the order currency
            $adjustment     = round((float) $adjustment, 2);
            $rate           = (float) $this->getOrder()->getBaseToOrderRate();
            $baseAdjustment = round($rate > 0 ? $adjustment / $rate : $adjustment, 2);

            $this->setBaseAdjustmentPositive($baseAdjustment);
            $this->setAdjustmentPositive($adjustment);
            $this->setGrandTotal($adjustment);
            $this->setBaseGrandTotal($baseAdjustment);
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