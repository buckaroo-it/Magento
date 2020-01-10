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
abstract class TIG_Buckaroo3Extended_Model_PaymentFee_Order_Creditmemo_Total_Fee_Abstract
    extends Mage_Sales_Model_Order_Creditmemo_Total_Tax
{
    /**
     * Xpath to the Buckaroo Payment fee setting.
     */
    const XPATH_BUCKAROO_FEE = 'buckaroo/%s/payment_fee';

    /**
     * Xpath to Buckaroo Payment fee tax class.
     */
    const XPATH_BUCKAROO_TAX_CLASS = 'tax/classes/buckaroo_fee';

    /**
     * Xpath to the Buckaroo Payment fee including tax setting.
     */
    const XPATH_BUCKAROO_FEE_INCLUDING_TAX = 'tax/calculation/buckaroo_fee_including_tax';

    /**
     * @return Mage_Tax_Model_Calculation
     */
    public function getTaxCalculation()
    {
        $taxCalculation = $this->_calculator;
        if ($taxCalculation) {
            return $taxCalculation;
        }

        $taxCalculation = Mage::getSingleton('tax/calculation');

        $this->setTaxCalculation($taxCalculation);
        return $taxCalculation;
    }

    /**
     * @param Mage_Tax_Model_Calculation $taxCalculation
     *
     * @return $this
     */
    public function setTaxCalculation(Mage_Tax_Model_Calculation $taxCalculation)
    {
        $this->_calculator = $taxCalculation;

        return $this;
    }

    /**
     * Get whether the Buckaroo Payment fee is incl. tax.
     *
     * @param int|Mage_Core_Model_Store|null $store
     *
     * @return bool
     */
    public function getFeeIsInclTax($store = null)
    {
        if (!$store) {
            $storeId = Mage::app()->getStore()->getId();
        } elseif ($store instanceof Mage_Core_Model_Store) {
            $storeId = $store->getId();
        } else {
            $storeId = $store;
        }

        return Mage::getStoreConfigFlag(self::XPATH_BUCKAROO_FEE_INCLUDING_TAX, $storeId);
    }

    /**
     * Get the tax request object for the current quote.
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool|Varien_Object
     */
    protected function _getBuckarooFeeTaxRequest(Mage_Sales_Model_Order $order)
    {
        $store = $order->getStore();
        $feeTaxClass      = Mage::getStoreConfig(self::XPATH_BUCKAROO_TAX_CLASS, $store);

        /**
         * If no tax class is configured for the Buckaroo Payment fee, there is no tax to be calculated.
         */
        if (!$feeTaxClass) {
            return false;
        }

        $taxCalculation   = $this->getTaxCalculation();
        $customerTaxClass = $order->getCustomerTaxClassId();
        $shippingAddress  = $order->getShippingAddress();
        $billingAddress   = $order->getBillingAddress();

        $request = $taxCalculation->getRateRequest(
            $shippingAddress,
            $billingAddress,
            $customerTaxClass,
            $store
        );

        $request->setProductClassId($feeTaxClass);

        return $request;
    }

    /**
     * Get the tax rate based on the previously created tax request.
     *
     * @param Varien_Object $request
     *
     * @return float
     */
    protected function _getBuckarooFeeTaxRate($request)
    {
        $rate = $this->getTaxCalculation()->getRate($request);

        return $rate;
    }

    /**
     * Get the fee tax based on the shipping address and tax rate.
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     * @param float                                $taxRate
     * @param float|null                           $fee
     * @param boolean                              $isInclTax
     *
     * @return float
     */
    protected function _getBuckarooFeeTax($address, $taxRate, $fee = null, $isInclTax = false)
    {
        if ($fee === null) {
            $fee = (float) $address->getBuckarooFee();
        }

        $taxCalculation = $this->getTaxCalculation();

        $feeTax = $taxCalculation->calcTaxAmount(
            $fee,
            $taxRate,
            $isInclTax,
            false
        );

        return $feeTax;
    }

    /**
     * Get the base fee tax based on the shipping address and tax rate.
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     * @param float                                $taxRate
     * @param float|null                           $fee
     * @param boolean                              $isInclTax
     *
     * @return float
     */
    protected function _getBaseBuckarooFeeTax($address, $taxRate, $fee = null, $isInclTax = false)
    {
        if ($fee === null) {
            $fee = (float) $address->getBaseBuckarooFee();
        }

        $taxCalculation = $this->getTaxCalculation();

        $baseFeeTax = $taxCalculation->calcTaxAmount(
            $fee,
            $taxRate,
            $isInclTax,
            false
        );

        return $baseFeeTax;
    }
}
