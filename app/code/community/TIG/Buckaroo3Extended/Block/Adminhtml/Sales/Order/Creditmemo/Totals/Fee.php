<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Creditmemo_Totals_Fee
    extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Totals
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
        /**
         * @var Mage_Adminhtml_Block_Sales_Order_Invoice_Totals $parent
         * @var Mage_Sales_Model_Order_Creditmemo $creditmemo
         */
        $parent     = $this->getParentBlock();
        $creditmemo = $parent->getCreditmemo();

        $fee     = $creditmemo->getBuckarooFee();
        $baseFee = $creditmemo->getBaseBuckarooFee();

        if ($fee < 0.01 || $baseFee < 0.01) {
            return $this;
        }

        $paymentMethod = $creditmemo->getOrder()->getPayment()->getMethod();
        $displayMode   = $this->getDisplayMode();

        $baseLabel = Mage::helper('buckaroo3extended')->getBuckarooFeeLabel($creditmemo->getStoreId(), $paymentMethod);

        if ($displayMode === self::DISPLAY_MODE_EXCL
            || $displayMode === self::DISPLAY_MODE_BOTH
            && $creditmemo->getId()
        ) {
            $label = $baseLabel;
            if ($displayMode === self::DISPLAY_MODE_BOTH) {
                $label .= ' (' . $this->getTaxLabel(false) . ')';
            }

            $total = new Varien_Object();
            $total->setLabel($label)
                  ->setValue($fee)
                  ->setBaseValue($baseFee)
                  ->setCode('buckaroo_fee');

            $parent->addTotal($total, 'subtotal_incl');
        }

        if ($displayMode === self::DISPLAY_MODE_INCL
            || $displayMode === self::DISPLAY_MODE_BOTH
            && $creditmemo->getId()
        ) {
            $label = $baseLabel;
            if ($displayMode === self::DISPLAY_MODE_BOTH) {
                $label .= ' (' . $this->getTaxLabel(true) . ')';
            }

            $totalInclTax = new Varien_Object();
            $totalInclTax->setLabel($label)
                         ->setValue($fee + $creditmemo->getBuckarooFeeTax())
                         ->setBaseValue($baseFee + $creditmemo->getBaseBuckarooFeeTax())
                         ->setCode('buckaroo_fee_incl_tax');

            $parent->addTotal($totalInclTax, 'subtotal_incl');
        }

        return $this;
    }

    /**
     * Get the display mode for the Buckaroo Payment fee.
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
