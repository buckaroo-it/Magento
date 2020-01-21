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
class Buckaroo_Buckaroo3Extended_Model_PaymentFee_Order_Invoice_Total_Subtotal
    extends Mage_Sales_Model_Order_Invoice_Total_Subtotal
{
    /**
     * Collect invoice subtotal.
     *
     * @param   Mage_Sales_Model_Order_Invoice $invoice
     *
     * @return  $this
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $subtotal       = 0;
        $baseSubtotal   = 0;
        $subtotalInclTax= 0;
        $baseSubtotalInclTax = 0;

        $order = $invoice->getOrder();

        /**
         * @var Mage_Sales_Model_Order_Invoice_Item $item
         */
        foreach ($invoice->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy()) {
                continue;
            }

            $item->calcRowTotal();

            $subtotal            += $item->getRowTotal();
            $baseSubtotal        += $item->getBaseRowTotal();
            $subtotalInclTax     += $item->getRowTotalInclTax();
            $baseSubtotalInclTax += $item->getBaseRowTotalInclTax();
        }

        $allowedSubtotal     = $order->getSubtotal() - $order->getSubtotalInvoiced();
        $baseAllowedSubtotal = $order->getBaseSubtotal() - $order->getBaseSubtotalInvoiced();

        $allowedSubtotalInclTax = $allowedSubtotal
            + $order->getHiddenTaxAmount()
            + $order->getTaxAmount()
            - $order->getTaxInvoiced()
            - $order->getHiddenTaxInvoiced();

        $baseAllowedSubtotalInclTax = $baseAllowedSubtotal
            + $order->getBaseHiddenTaxAmount()
            + $order->getBaseTaxAmount()
            - $order->getBaseTaxInvoiced()
            - $order->getBaseHiddenTaxInvoiced();

        /**
         * Check if shipping tax calculation and Buckaroo Payment fee tax is included to current invoice.
         *
         * @var Mage_Sales_Model_Order_Invoice $previousInvoice
         */
        $includeShippingTax = true;
        $includeBuckarooFeeTax = true;
        foreach ($invoice->getOrder()->getInvoiceCollection() as $previousInvoice) {
            if ($previousInvoice->isCanceled()) {
                continue;
            }

            if ($previousInvoice->getShippingAmount()) {
                $includeShippingTax = false;
            }

            if ($previousInvoice->getBuckarooFeeTax()) {
                $includeBuckarooFeeTax = false;
            }
        }

        if ($includeShippingTax) {
            $allowedSubtotalInclTax     -= $order->getShippingTaxAmount();
            $baseAllowedSubtotalInclTax -= $order->getBaseShippingTaxAmount();
        } else {
            $allowedSubtotalInclTax     += $order->getShippingHiddenTaxAmount();
            $baseAllowedSubtotalInclTax += $order->getBaseShippingHiddenTaxAmount();
        }

        if ($includeBuckarooFeeTax) {
            $allowedSubtotalInclTax     -= $order->getBuckarooFeeTax();
            $baseAllowedSubtotalInclTax -= $order->getBaseBuckarooFeeTax();
        }

        if ($invoice->isLast()) {
            $subtotal = $allowedSubtotal;
            $baseSubtotal = $baseAllowedSubtotal;
            $subtotalInclTax = $allowedSubtotalInclTax;
            $baseSubtotalInclTax  = $baseAllowedSubtotalInclTax;
        } else {
            $subtotal = min($allowedSubtotal, $subtotal);
            $baseSubtotal = min($baseAllowedSubtotal, $baseSubtotal);
            $subtotalInclTax = min($allowedSubtotalInclTax, $subtotalInclTax);
            $baseSubtotalInclTax = min($baseAllowedSubtotalInclTax, $baseSubtotalInclTax);
        }

        $invoice->setSubtotal($subtotal);
        $invoice->setBaseSubtotal($baseSubtotal);
        $invoice->setSubtotalInclTax($subtotalInclTax);
        $invoice->setBaseSubtotalInclTax($baseSubtotalInclTax);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $subtotal);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseSubtotal);
        return $this;
    }
}
