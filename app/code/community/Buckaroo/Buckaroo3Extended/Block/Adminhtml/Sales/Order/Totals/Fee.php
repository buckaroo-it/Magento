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
class Buckaroo_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Totals_Fee extends Mage_Adminhtml_Block_Sales_Order_Totals
{
    /**
     * Display modes for the Buckaroo Payment fee.
     */
    const DISPLAY_MODE_EXCL = 1;
    const DISPLAY_MODE_INCL = 2;
    const DISPLAY_MODE_BOTH = 3;

    /**
     * Xpath to the Buckaroo Payment fee display mode setting.
     */
    const XPATH_DISPLAY_MODE_BUCKAROO_FEE = 'tax/sales_display/buckaroo_fee';

    /**
     * Initialize order totals array
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $order  = $this->getOrder();

        $fee     = $order->getBuckarooFee();
        $baseFee = $order->getBaseBuckarooFee();

        if ($fee < 0.01 || $baseFee < 0.01) {
            return $this;
        }

        $paymentMethod = $order->getPayment()->getMethod();

        $displayMode = $this->getDisplayMode();
        $baseLabel = Mage::helper('buckaroo3extended')->getBuckarooFeeLabel($order->getStoreId(), $paymentMethod);

        if ($displayMode === self::DISPLAY_MODE_EXCL || $displayMode === self::DISPLAY_MODE_BOTH) {
            $label = $baseLabel;
            if ($displayMode === self::DISPLAY_MODE_BOTH) {
                $label .= ' (' . $this->getTaxLabel(false) . ')';
            }

            $total = new Varien_Object();
            $total->setLabel($label)
                  ->setValue($fee)
                  ->setBaseValue($baseFee)
                  ->setCode('buckaroo_fee');

            $parent->addTotalBefore($total, 'shipping');
        }

        if ($displayMode === self::DISPLAY_MODE_INCL || $displayMode === self::DISPLAY_MODE_BOTH) {
            $label = $baseLabel;
            if ($displayMode === self::DISPLAY_MODE_BOTH) {
                $label .= ' (' . $this->getTaxLabel(true) . ')';
            }

            $totalInclTax = new Varien_Object();
            $totalInclTax->setLabel($label)
                         ->setValue($fee + $order->getBuckarooFeeTax())
                         ->setBaseValue($baseFee + $order->getBaseBuckarooFeeTax())
                         ->setCode('buckaroo_fee_incl_tax');

            $parent->addTotalBefore($totalInclTax, 'shipping');
        }

        return $this;
    }

    /**
     * Get the display mode for the Buckaroo fee.
     *
     * @return int
     */
    public function getDisplayMode()
    {
        $displayMode = (int) Mage::getStoreConfig(self::XPATH_DISPLAY_MODE_BUCKAROO_FEE, $this->_store);

        return $displayMode;
    }

    /**
     * Get the tax label for either incl. or excl. tax.
     *
     * @param boolean $inclTax
     *
     * @return string
     */
    public function getTaxLabel($inclTax = false)
    {
        $taxLabel = Mage::helper('tax')->getIncExcText($inclTax);

        return $taxLabel;
    }
}
