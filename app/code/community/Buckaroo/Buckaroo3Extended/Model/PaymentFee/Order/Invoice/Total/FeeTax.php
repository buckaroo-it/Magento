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
class Buckaroo_Buckaroo3Extended_Model_PaymentFee_Order_Invoice_Total_FeeTax
    extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    /**
     * @param Mage_Sales_Model_Order_Invoice $invoice
     *
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();

        /**
         * The COD fee tax is always added to the first invoice, so if this order already has invoices, we don't have to
         * add anything.
         */
        if ($order->hasInvoices()) {
            return $this;
        }

        /**
         * Get the COD fee tax amounts.
         */
        $feeTax     = $order->getBuckarooFeeTax();
        $baseFeeTax = $order->getBaseBuckarooFeeTax();

        /**
         * If no COD fee tax is set, there is nothing to add/
         */
        if ($feeTax < 0.01 || $baseFeeTax < 0.01) {
            return $this;
        }

        /**
         * Add the COD fee tax amounts to the invoice.
         */
        $invoice->setBuckarooFeeTax($feeTax)
                ->setBaseBuckarooFeeTax($baseFeeTax)
                ->setTaxAmount($invoice->getTaxAmount() + $feeTax)
                ->setBaseTaxAmount($invoice->getBaseTaxAmount() + $baseFeeTax);

        /**
         * For all versions except 1.13.0.X and 1.8.0.X we need to add the Payment fee tax to the grand total amounts.
         */
        $helper = Mage::helper('buckaroo3extended');

        //fix for Magento 1.6.2.0 for showing correctly taxes in the order totals
        if (!$helper->isEnterprise() && version_compare(Mage::getVersion(), '1.6.2.0', '==')) {
            $grandTotal     = $invoice->getGrandTotal();
            $baseGrandTotal = $invoice->getBaseGrandTotal();
        } else {
            $grandTotal     = $invoice->getGrandTotal() + $feeTax;
            $baseGrandTotal = $invoice->getBaseGrandTotal() + $baseFeeTax;
        }

        $invoice->setGrandTotal($grandTotal)
                ->setBaseGrandTotal($baseGrandTotal);

        /**
         * Update the order's COD fee tax amounts.
         */
        $order->setBuckarooFeeTaxInvoiced($feeTax)
              ->setBaseBuckarooFeeTaxInvoiced($baseFeeTax);

        return $this;
    }
}
