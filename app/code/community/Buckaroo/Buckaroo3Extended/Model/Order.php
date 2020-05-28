<?php
class Buckaroo_Buckaroo3Extended_Model_Order extends Mage_Sales_Model_Order
{
    public function canInvoice()
    {
        $result = parent::canInvoice();

        Mage::helper('buckaroo3extended')->devLog(__METHOD__, 1, $result);

        if (
            $result
            &&
            ($this->getPayment()->getMethod() == 'buckaroo3extended_afterpay20')
            &&
            Mage::getStoreConfig(
                'buckaroo/buckaroo3extended_afterpay20/custom_amount_capture',
                Mage::app()->getStore()->getStoreId()
            )
        ) {
                Mage::helper('buckaroo3extended')->devLog(__METHOD__, 2, [
                    round($this->getData('grand_total'),2),
                    round($this->getData('total_invoiced'), 2)
                ]);

                return round($this->getData('grand_total'),2) > round($this->getData('total_invoiced'), 2);
        }

        return $result;
    }

}