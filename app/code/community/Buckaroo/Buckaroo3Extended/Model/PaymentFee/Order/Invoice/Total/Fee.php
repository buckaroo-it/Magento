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
class Buckaroo_Buckaroo3Extended_Model_PaymentFee_Order_Invoice_Total_Fee
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
         * The COD fee is always added to the first invoice, so if this order already has invoices, we don't have to add
         * anything.
         */
        if ($order->hasInvoices()) {
            return $this;
        }

        /**
         * Get the COD fee amounts.
         */
        $fee     = $order->getBuckarooFee();
        $baseFee = $order->getBaseBuckarooFee();

        /**
         * If no COD fee is set, there is nothing to add/
         */
        if ($fee < 0.01 || $baseFee < 0.01) {
            return $this;
        }

        /**
         * Add the COD fee amounts to the invoice and update the amounts for the order.
         */
        $grandTotal = $invoice->getGrandTotal();
        $baseGrandTotal = $invoice->getBaseGrandTotal();

        $invoice->setBuckarooFee($fee)
                ->setBaseBuckarooFee($baseFee)
                ->setGrandTotal($grandTotal + $fee)
                ->setBaseGrandTotal($baseGrandTotal + $baseFee);

        $order->setBuckarooFeeInvoiced($fee)
              ->setBaseBuckarooFeeInvoiced($baseFee);

        return $this;
    }
}
