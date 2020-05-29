<?php
class Buckaroo_Buckaroo3Extended_Model_Invoice extends Mage_Sales_Model_Order_Invoice
{
    public function capture()
    {
        Mage::helper('buckaroo3extended')->devLog(__METHOD__, 1);

        if (
            ($this->getOrder()->getPayment()->getMethod() == 'buckaroo3extended_afterpay20')
            &&
            Mage::getStoreConfig(
                'buckaroo/buckaroo3extended_afterpay20/custom_amount_capture',
                Mage::app()->getStore()->getStoreId()
            )
        ) {
            Mage::helper('buckaroo3extended')->devLog(__METHOD__, 2);
            if (
                ($paramInvoice = Mage::app()->getRequest()->getParam('invoice'))
                &&
                !empty($paramInvoice['custom_amount_capture'])
                &&
                ($paramInvoice['custom_amount_capture'] = trim($paramInvoice['custom_amount_capture']))
                &&
                ($paramInvoice['custom_amount_capture'] > 0)
            ) {

                Mage::helper('buckaroo3extended')->devLog(__METHOD__, 3, [
                    $paramInvoice['custom_amount_capture'],
                    round($this->getOrder()->getData('grand_total') - $this->getOrder()->getData('total_invoiced'), 2)
                ]);

                if (
                    $paramInvoice['custom_amount_capture']
                    <=
                    round($this->getOrder()->getData('grand_total') - $this->getOrder()->getData('total_invoiced'), 2)
                ) {
                    $this->setBaseGrandTotal($paramInvoice['custom_amount_capture']);
                    $this->setGrandTotal($paramInvoice['custom_amount_capture']);

                    $this->setShippingTaxAmount(0);
                    $this->setTaxAmount(0);
                    $this->setBaseTaxAmount(0);
                    $this->setBaseShippingTaxAmount(0);
                    $this->setBaseDiscountAmount(0);
                    $this->setShippingAmount(0);
                    $this->setSubtotalInclTax(0);
                    $this->setBaseSubtotalInclTax(0);
                    $this->setBaseShippingAmount(0);
                    $this->setSubtotal(0);
                    $this->setBaseSubtotal(0);
                    $this->setDiscountAmount(0);
                    $this->setHiddenTaxAmount(0);
                    $this->setBaseHiddenTaxAmount(0);
                    $this->setShippingHiddenTaxAmount(0);
                    $this->setBaseShippingHiddenTaxAmount(0);
                    $this->setShippingInclTax(0);
                    $this->setBaseShippingInclTax(0);
                    $this->setBuckarooFee(0);
                    $this->setBaseBuckarooFee(0);
                    $this->setBuckarooFeeTax(0);
                    $this->setBaseBuckarooFeeTax(0);

                    $this->save();

                    foreach ($this->getOrder()->getAllItems() as $item) {
                        $item->setQtyInvoiced(0);
                        $item->save();
                    }
                } else {
                    Mage::throwException(Mage::helper('sales')->__('Capture amount is too large'));
                    return false;
                }

            } else {
                Mage::throwException(Mage::helper('sales')->__('Capture amount should be positive'));
                return false;
            }
        }

        if (
            ($this->getOrder()->getPayment()->getMethod() == 'buckaroo3extended_afterpay20')
            &&
            ($this->getGrandTotal() == 0)
        ) {
            Mage::throwException(Mage::helper('sales')->__('Capture amount should be positive'));
            return false;
        }

        return parent::capture();
    }

    /*
    public function getAllItems()
    {

            return array();
            //return parent::getAllItems();
    }
    */

}

