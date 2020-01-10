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
class TIG_Buckaroo3Extended_Model_PaymentFee_Order_Creditmemo_Total_Fee
    extends TIG_Buckaroo3Extended_Model_PaymentFee_Order_Creditmemo_Total_Fee_Abstract
{
    /**
     * Xpath to the Buckaroo Payment fee including tax setting.
     */
    const XPATH_BUCKAROO_FEE_INCLUDING_TAX = 'tax/calculation/buckaroo_fee_including_tax';

    /** @var null|TIG_Buckaroo3Extended_Helper_Data */
    protected $_helper = null;

    /**
     * @return TIG_Buckaroo3Extended_Helper_Data
     */
    protected function getHelper()
    {
        if ($this->_helper === null) {
            $helper = Mage::helper('buckaroo3extended');
            $this->_helper = $helper;
        }

        return $this->_helper;
    }

    /**
     * Get the Buckaroo Payment fee total amount.
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     *
     * @return $this
     *
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $fee     = $creditmemo->getBuckarooFee();
        $baseFee = $creditmemo->getBaseBuckarooFee();

        $creditmemoInvoice = $creditmemo->getInvoice();

        if ($creditmemoInvoice && ($creditmemoInvoice->getBuckarooFee() < 0.01
                || $creditmemoInvoice->getBaseBuckarooFee() < 0.01)) {
            return $this;
        }

        /**
         * If the creditmemo has a fee already, we only need to set the totals. This is the case for existing
         * creditmemos that are being viewed.
         */
        if ($fee && $baseFee) {
            $this->_updateCreditmemoTotals($creditmemo, $order, $fee, $baseFee);

            return $this;
        }

        /**
         * If we are currently in the backend and logged in, we need to check the POST parameters to see if any fee
         * amount is to be refunded.
         */
        if ($this->getHelper()->isAdmin() && Mage::getSingleton('admin/session')->isLoggedIn()) {
            /**
             * This is unfortunately the only way to determine the fee amount that needs to be refunded without
             * rewriting a core class. If anybody knows of a better way, please let us know at
             * servicedesk@tig.nl.
             */
            $creditmemoParameters = Mage::app()->getRequest()
                                               ->getParam('creditmemo', array());

            if (isset($creditmemoParameters['buckaroo_fee'])
                && $creditmemoParameters['buckaroo_fee'] !== null
            ) {
                $this->_updateCreditmemoTotalsFromParams($creditmemo, $order, $creditmemoParameters);

                return $this;
            }
        }

        /**
         * If none of the above are true, we are creating a new creditmemo and need to show the fee amounts that may be
         * refunded (if any).
         */
        $fee     = $order->getBuckarooFee() - $order->getBuckarooFeeRefunded();
        $baseFee = $order->getBaseBuckarooFee() - $order->getBaseBuckarooFeeRefunded();

        if ($fee && $baseFee) {
            $this->_updateCreditmemoTotals($creditmemo, $order, $fee, $baseFee);

            return $this;
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Mage_Sales_Model_Order            $order
     * @param float                             $fee
     * @param float                             $baseFee
     *
     * @return $this
     */
    protected function _updateCreditmemoTotals(
        Mage_Sales_Model_Order_Creditmemo $creditmemo,
        Mage_Sales_Model_Order $order,
        $fee,
        $baseFee
        // @codingStandardsIgnoreLine
    ) {
        $creditmemo->setBuckarooFee($fee)
                   ->setBaseBuckarooFee($baseFee)
                   ->setGrandTotal($creditmemo->getGrandTotal() + $fee)
                   ->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseFee);

        $order->setBuckarooFeeRefunded($order->getBuckarooFeeRefunded() + $fee)
              ->setBaseBuckarooFeeRefunded($order->getBaseBuckarooFeeRefunded() + $baseFee);

        return $this;
    }

    /**
     * Update the creditmemo's totals based on POST params.
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Mage_Sales_Model_Order            $order
     * @param array                             $creditmemoParameters
     *
     * @return $this
     *
     * @throws Mage_Exception
     */
    protected function _updateCreditmemoTotalsFromParams(
        Mage_Sales_Model_Order_Creditmemo $creditmemo,
        Mage_Sales_Model_Order $order,
        array $creditmemoParameters
        // @codingStandardsIgnoreLine
    ) {
        /**
         * Get the fee amounts that are to be refunded.
         */
        $baseFee = (float) $creditmemoParameters['buckaroo_fee'];

        /**
         * If the fee was entered incl. tax calculate the fee without tax.
         */
        if ($this->getFeeIsInclTax($order->getStore())) {
            $baseFee = $this->_getBuckarooFeeExclTax($baseFee, $order);
        }

        /**
         * Get the order's Buckaroo Payment fee amounts.
         */
        $orderFee             = $order->getBuckarooFee();
        $orderFeeRefunded     = $order->getBuckarooFeeRefunded();
        $orderBaseFee         = $order->getBaseBuckarooFee();
        $orderBaseFeeRefunded = $order->getBaseBuckarooFeeRefunded();

        /**
         * If the total amount refunded exceeds the available fee amount, we have a rounding error. Modify the fee
         * amounts accordingly.
         */
        $totalBaseFee = $baseFee - $orderBaseFee - $orderBaseFeeRefunded;
        if ($totalBaseFee < 0.01 && $totalBaseFee > -0.01) {
            $baseFee = $orderBaseFee - $orderBaseFeeRefunded;
        }

        $fee = $baseFee * $order->getBaseToOrderRate();

        $totalFee = $fee - $orderFee - $orderFeeRefunded;
        if ($totalFee < 0.01 && $totalFee > -0.01) {
            $fee = $orderFee - $orderFeeRefunded;
        }

        if (round($orderBaseFeeRefunded + $baseFee, 4) > $orderBaseFee) {
            // @codingStandardsIgnoreLine
            throw new Mage_Exception(
                $this->getHelper()->__(
                    'Maximum Buckaroo Payment fee amount available to refunds is %s.',
                    $order->formatPriceTxt(
                        $orderBaseFee - $orderBaseFeeRefunded
                    )
                )
            );
        }

        /**
         * Update the creditmemo totals with the new amounts.
         */
        $creditmemo->setBuckarooFee($fee)
                   ->setBaseBuckarooFee($baseFee)
                   ->setGrandTotal($creditmemo->getGrandTotal() + $fee)
                   ->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseFee);

        $order->setBuckarooFeeRefunded($orderFeeRefunded + $fee)
              ->setBaseBuckarooFeeRefunded($orderBaseFeeRefunded + $baseFee);

        return $this;
    }

    /**
     * Gets the configured Buckaroo Payment fee excl. tax for a given quote.
     *
     * @param float                  $fee
     * @param Mage_Sales_Model_Order $order
     *
     * @return float|int
     */
    protected function _getBuckarooFeeExclTax($fee, Mage_Sales_Model_Order $order)
    {

        /**
         * Build a tax request to calculate the fee tax.
         */
        $taxRequest = $this->_getBuckarooFeeTaxRequest($order);

        if (!$taxRequest) {
            return $fee;
        }

        /**
         * Get the tax rate for the request.
         */
        $taxRate = $this->_getBuckarooFeeTaxRate($taxRequest);

        if (!$taxRate || $taxRate <= 0) {
            return $fee;
        }

        /**
         * Remove the tax from the fee.
         */
        $feeTax = $this->_getBuckarooFeeTax($order->getShippingAddress(), $taxRate, $fee, true);
        $fee -= $feeTax;

        return $fee;
    }
}
