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
class TIG_Buckaroo3Extended_Model_PaymentFee_Service
{
    /**
     * Xpath to Buckaroo fee tax class.
     */
    const XPATH_BUCKAROO_FEE_TAX_CLASS = 'tax/classes/buckaroo_fee';

    /**
     * Add Buckaroo Payment fee tax info to the full tax info array.
     *
     * This is a really annoying hack to fix the problem where the full tax info does not include the custom Buckaroo
     * Payment fee tax info. Magento only supports tax info from shipping tax or product tax by default
     * (see Mage_Tax_Helper_Data::getCalculatedTaxes()). If anybody knows of a better way to fix this (that does not
     * require a core rewrite) please let us know at servicedesk@tig.nl.
     *
     * @param array                                                                                   $fullInfo
     * @param Mage_Sales_Model_Order|Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $source
     * @param Mage_Sales_Model_Order                                                                  $order
     *
     * @return array
     *
     * @see Mage_Tax_Helper_Data::getCalculatedTaxes()
     */
    public function addBuckarooFeeTaxInfo($fullInfo, $source, Mage_Sales_Model_Order $order)
    {
        $feeTax = (float) $order->getBuckarooFeeTax();
        if ($feeTax <= 0) {
            return $fullInfo;
        }

        /**
         * There are 3 possible ways to add the Payment fee tax info:
         *  - Go through all tax info records of an order and add the Payment fee info to the record with the same
         * title and a discrepancy in the recorded and expected amount.
         *  - Add a missing tax info record.
         *  - Recalculate the tax info for the Payment fee and update the amount of the tax record with the same title.
         */
        $orderClassName = Mage::getConfig()->getModelClassName('sales/order');
        if ($source instanceof $orderClassName) {
            $fullInfo = $this->_updateTaxAmountForTaxInfo($order, $fullInfo);
        } else {
            /**
             * Try to find a tax record that does not have a corresponding tax item record.
             */
            $taxItemCollection = Mage::getResourceModel('tax/sales_order_tax_item_collection');
            $taxItemCollection->addFieldToSelect('tax_id');
            $taxItemCollection->getSelect()->distinct();

            $taxItemIds = $taxItemCollection->getColumnValues('tax_id');

            $taxCollection = Mage::getResourceModel('sales/order_tax_collection')
                                 ->addFieldToFilter('order_id', array('eq'  => $order->getId()))
                                 ->addFieldToFilter('tax_id', array('nin' => $taxItemIds));

            /**
             * If we have found a missing record, we need to add it with the COD fee tax info. Otherwise we need to
             * recreate the entire tax request for the COD fee tax so we can match the title to an existing tax item
             * record.
             */
            if ($taxCollection->getSize()) {
                $fullInfo = $this->_addBuckarooFeeTaxInfoFromCollection($taxCollection, $fullInfo, $source);
            } else {
                $fullInfo = $this->_addBuckarooFeeTaxInfoFromRequest($order, $fullInfo, $source);
            }
        }

        return $fullInfo;
    }

    /**
     * Add Buckaroo Payment fee tax info by updating an incorrect tax record.
     *
     * @param Mage_Sales_Model_Order $order
     * @param array $fullInfo
     *
     * @return array
     */
    protected function _updateTaxAmountForTaxInfo($order, $fullInfo)
    {
        $taxCollection = Mage::getResourceModel('sales/order_tax_collection')
                             ->addFieldToSelect('amount')
                             ->addFieldToFilter('order_id', array('eq' => $order->getId()));

        /**
         * Go through each tax record and update the tax info entry that has the same title, but a different amount.
         */
        foreach ($taxCollection as $tax) {
            foreach ($fullInfo as $key => $taxInfo) {
                if ($tax->getTitle() == $taxInfo['title'] && $tax->getAmount() != $taxInfo['tax_amount']) {
                    /**
                     * Update the amounts.
                     */
                    $fullInfo[$key]['tax_amount']      = $tax->getAmount();
                    $fullInfo[$key]['base_tax_amount'] = $tax->getBaseAmount();
                }
            }
        }

        return $fullInfo;
    }

    /**
     * Add Buckaroo Payment fee tax info by updating or adding a missing tax record.
     *
     * @param Mage_Sales_Model_Resource_Order_Tax_Collection                   $taxCollection
     * @param array                                                            $fullInfo
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $source
     *
     * @return array
     */
    protected function _addBuckarooFeeTaxInfoFromCollection($taxCollection, $fullInfo, $source)
    {
        /**
         * Go through all tax records and add the Buckaroo Payment fee tax to the entry that has the
         * right title. If no entry exists with that title, add it.
         */
        foreach ($taxCollection as $tax) {
            foreach ($fullInfo as $key => $taxInfo) {
                /**
                 * Update an existing entry.
                 */
                if ($taxInfo['title'] == $tax->getTitle()) {
                    $fullInfo[$key]['tax_amount']      += $source->getBuckarooFeeTax();
                    $fullInfo[$key]['base_tax_amount'] += $source->getBaseBuckarooFeeTax();

                    break(2);
                }
            }

            /**
             * Add a missing entry.
             */
            $fullInfo[] = array(
                'tax_amount'      => $source->getBuckarooFeeTax(),
                'base_tax_amount' => $source->getBaseBuckarooFeeTax(),
                'title'           => $tax->getTitle(),
                'percent'         => $tax->getPercent(),
            );
        }

        return $fullInfo;
    }

    /**
     * Add Buckaroo Payment fee tax info by recreating the tax request.
     *
     * @param Mage_Sales_Model_Order                                           $order
     * @param array                                                            $fullInfo
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $source
     *
     * @return array
     */
    protected function _addBuckarooFeeTaxInfoFromRequest($order, $fullInfo, $source)
    {
        $store = $order->getStore();
        $taxCalculation = Mage::getSingleton('tax/calculation');

        /**
         * Recalculate the tax request.
         */
        $customerTaxClass = $order->getCustomerTaxClassId();
        $shippingAddress  = $order->getShippingAddress();
        $billingAddress   = $order->getBillingAddress();
        $codTaxClass      = Mage::getStoreConfig(self::XPATH_BUCKAROO_FEE_TAX_CLASS, $store);

        $taxRequest = $taxCalculation->getRateRequest(
            $shippingAddress,
            $billingAddress,
            $customerTaxClass,
            $store
        );

        $taxRequest->setProductClassId($codTaxClass);

        /**
         * If the tax request fails, there is nothing more we can do. This might occur, if the tax rules have been
         * changed since this order was placed. Unfortunately there is nothing we can do about this.
         */
        if (!$taxRequest) {
            return $fullInfo;
        }

        /**
         * Get the applied rates.
         */
        $appliedRates = Mage::getSingleton('tax/calculation')
                            ->getAppliedRates($taxRequest);

        if (!isset($appliedRates[0]['rates'][0]['title'])) {
            return $fullInfo;
        }

        /**
         * Get the tax title from the applied rates.
         */
        $buckarooFeeTaxTitle = $appliedRates[0]['rates'][0]['title'];

        /**
         * Fo through all tax info entries and try to match the title.
         */
        foreach ($fullInfo as $key => $taxInfo) {
            if ($taxInfo['title'] == $buckarooFeeTaxTitle) {
                /**
                 * Update the tax info entry with the COD fee tax.
                 */
                $fullInfo[$key]['tax_amount']      += $source->getBuckarooFeeTax();
                $fullInfo[$key]['base_tax_amount'] += $source->getBaseBuckarooFeeTax();
                break;
            }
        }

        return $fullInfo;
    }
}
