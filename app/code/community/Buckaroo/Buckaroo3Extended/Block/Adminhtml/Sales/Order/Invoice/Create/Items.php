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
class Buckaroo_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Invoice_Create_Items extends Mage_Adminhtml_Block_Sales_Order_Invoice_Create_Items {
    /**
     * Retrieve order totalbar block data
     *
     * @return array
     */
    public function getOrderTotalbarData()
    {
        $totalbarData = parent::getOrderTotalbarData();

        if (
            ($this->getOrder()->getPayment()->getMethod() == 'buckaroo3extended_afterpay20')
            &&
            Mage::getStoreConfig(
                'buckaroo/buckaroo3extended_afterpay20/custom_amount_capture',
                Mage::app()->getStore()->getStoreId()
            )
        ) {
            $paid = $this->displayPrices(
                $this->getOrder()->getData('base_total_invoiced'),
                $this->getOrder()->getData('total_invoiced')
            );

            $totalbarData[0] = array(
                Mage::helper('sales')->__('Paid Amount'), $paid, false
            );

            $remainingAmount = $this->displayPrices(
                $this->getOrder()->getData('base_grand_total') - $this->getOrder()->getData('base_total_invoiced'),
                $this->getOrder()->getData('grand_total') - $this->getOrder()->getData('total_invoiced')
            );

            array_splice(
                $totalbarData,
                1,
                0,
                array(array(Mage::helper('sales')->__('Remaining Amount'), $remainingAmount, false))
            );
        }

        return $totalbarData;
    }
}
