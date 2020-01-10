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
class TIG_Buckaroo3Extended_Model_PaymentFee_Quote_Address_Total_Fee
    extends TIG_Buckaroo3Extended_Model_PaymentFee_Quote_Address_Total_Fee_Abstract
{
    /**
     * Xpath to Idev's OneStepCheckout's 'display_tax_included' setting.
     */
    const XPATH_ONESTEPCHECKOUT_DISPLAY_TAX_INCLUDED = 'onestepcheckout/general/display_tax_included';

    /**
     * Module name used by OneStepCheckout.
     */
    const ONESTEPCHECKOUT_MODULE_NAME = 'onestepcheckout';

    /**
     * The code of this 'total'.
     *
     * @var string
     */
    protected $_totalCode = 'buckaroo_fee';

    /**
     * Collect the Buckaroo fee for the given address.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return $this
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        /**
         * We can only add the fee to the shipping address.
         */
        if ($address->getAddressType() != 'shipping') {
            return $this;
        }

        $quote = $address->getQuote();
        $store = $quote->getStore();

        if (!$quote->getId()) {
            return $this;
        }

        $items = $address->getAllItems();
        if (empty($items)) {
            return $this;
        }

        /**
         * First, reset the fee amounts to 0 for this address and the quote.
         */
        $address->setBuckarooFee(0)
                ->setBaseBuckarooFee(0);

        $quote->setBuckarooFee(0)
              ->setBaseBuckarooFee(0);

        /**
         * Check if the order was placed using Buckaroo
         */
        $paymentMethod = $quote->getPayment()->getMethod();

        if (strpos($paymentMethod, 'buckaroo') === false) {
            return $this;
        }

        /**
         * Get the fee amount.
         */
        $baseFee = $this->_getPaymentFee($quote, $paymentMethod);
        if ($baseFee <= 0) {
            return $this;
        }

        /**
         * Convert the fee to the base fee amount.
         */
        $fee = $store->convertPrice($baseFee);
        /**
         * Set the fee for the address and quote.
         */
        $address->setBuckarooFee($fee)
                ->setBaseBuckarooFee($baseFee);

        $quote->setBuckarooFee($fee)
              ->setBaseBuckarooFee($baseFee);

        /**
         * Update the address' grand total amounts.
         */
        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $baseFee);
        $address->setGrandTotal($address->getGrandTotal() + $fee);

        return $this;
    }

    /**
     * Fetch the fee.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return $this
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getBuckarooFee();

        if ($amount <= 0) {
            return $this;
        }

        $quote   = $address->getQuote();
        $storeId = $quote->getStoreId();
        $paymentMethod = $quote->getPayment()->getMethod();

        /**
         * Add the Buckaroo Payment fee tax for OSC if the 'display_tax_included' setting is turned on.
         */
        if (Mage::app()->getRequest()->getModuleName() == self::ONESTEPCHECKOUT_MODULE_NAME
            && Mage::getStoreConfigFlag(self::XPATH_ONESTEPCHECKOUT_DISPLAY_TAX_INCLUDED, $storeId)
        ) {
            $amount += $address->getBuckarooFeeTax();
        }

        $address->addTotal(
            array(
                'code'  => $this->getCode(),
                'title' => Mage::helper('buckaroo3extended')->getBuckarooFeeLabel($storeId, $paymentMethod, $amount),
                'value' => $amount,
            )
        );

        return $this;
    }

    /**
     * get payment fee before the paymentmethod is selected, based on tax settings
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $paymentMethod
     * @return float|string
     */
    public function getPaymentFeeBeforeSelect(Mage_Sales_Model_Quote $quote, $paymentMethod = '')
    {
        $store   = $quote->getStore();
        $storeId = $store->getId();

        $configuredFee = Mage::getStoreConfig(
            sprintf(self::XPATH_BUCKAROO_FEE, $quote->getPayment()->getMethod()),
            $storeId
        );

        if (strpos($configuredFee, '%') !== false) {
            return $configuredFee;
        }

        $baseFee = $this->_getPaymentFee($quote, $paymentMethod);
        $fee     = $store->convertPrice($baseFee);
        $includeTax  = false;
        if (!$this->getFeeIsInclTax($storeId)) {
            $includeTax = true;
        }

        $paymentFee  = Mage::helper('tax')->getShippingPrice($fee, $includeTax, $quote->getShippingAddress());

        return $paymentFee;
    }


    /**
     * Gets the configured Buckaroo Payment fee excl. tax for a given quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $paymentMethod
     *
     * @return float|int
     */
    protected function _getPaymentFee(Mage_Sales_Model_Quote $quote, $paymentMethod = '')
    {
        $storeId = $quote->getStoreId();

        /**
         * Get the fee as configured by the merchant.
         */
        if (empty($paymentMethod)) {
            $paymentMethod = $quote->getPayment()->getMethod();
        }

        if (!$paymentMethod) {
            return 0;
        }

        $fee = Mage::getStoreConfig(sprintf(self::XPATH_BUCKAROO_FEE, $paymentMethod), $storeId);

        /**
         * Determine if the configured fee is a percentage or a flat amount.
         */
        if (strpos($fee, '%') !== false) {
            $this->_feeIsPercentage = true;

            /**
             * If the fee is a percentage, get the configured percentage value and determine over which part of the
             * quote this percentage needs to be calculated.
             */
            $percentage = floatval(trim($fee));
            if (!$quote->isVirtual()) {
                $address = $quote->getShippingAddress();
            } else {
                $address = $quote->getBillingAddress();
            }

            $calculationAmount = $this->getCalculationAmount($storeId, $address);

            /**
             * Calculate the flat fee.
             */
            if ($calculationAmount !== false && $calculationAmount > 0) {
                $fee = $calculationAmount * ($percentage / 100);
            } else {
                $fee = 0;
            }
        } else {
            $fee = $this->convertToFloat($fee);
        }

        if ($fee <= 0) {
            return 0;
        }

        /**
         * If the fee is entered without tax, return the fee amount. Otherwise, we need to calculate and remove the tax.
         */
        $feeIsIncludingTax = $this->getFeeIsInclTax($storeId);
        if (!$feeIsIncludingTax) {
            return $fee;
        }

        /**
         * Build a tax request to calculate the fee tax.
         */
        $taxRequest = $this->_getBuckarooFeeTaxRequest($quote);

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
        $feeTax = $this->_getBuckarooFeeTax($quote->getShippingAddress(), $taxRate, $fee, true);
        $fee -= $feeTax;

        return $fee;
    }
    
    protected function convertToFloat($fee)
    {
        $fee = str_replace(",",".", $fee);
        $fee = preg_replace('/\.(?=.*\.)/', '', $fee);
        
        return floatval($fee);
    }

    protected function getCalculationAmount($storeId, $address)
    {
        $calculationAmount = false;

        $feePercentageMode = Mage::getStoreConfig(self::XPATH_BUCKAROO_FEE_PERCENTAGE_MODE, $storeId);
        switch ($feePercentageMode) {
            case 'subtotal':
                $calculationAmount = $address->getBaseSubtotal()
                    - $address->getBaseDiscountAmount();

                $this->_feeIsInclTax = false;
                break;
            case 'subtotal_incl_tax':
                $calculationAmount = $address->getBaseSubtotalInclTax()
                    - $address->getBaseDiscountAmount();

                $this->_feeIsInclTax = true;
                break;
            case 'grandtotal':
                $calculationAmount = $address->getBaseSubtotalInclTax()
                    + $address->getBaseShippingInclTax()
                    - $address->getBaseDiscountAmount();

                $this->_feeIsInclTax = true;
                break;
            //no default
        }

        return $calculationAmount;
    }
}
