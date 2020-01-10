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
 * @category    TIG
 * @package     TIG_Buckaroo3Extended
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

class TIG_Buckaroo3Extended_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Xpath to Buckaroo fee tax class.
     */
    const XPATH_BUCKAROO_FEE_TAX_CLASS = 'tax/classes/buckaroo_fee';
    
    /**
     * Status codes for Buckaroo
     */
    const BUCKAROO_SUCCESS           = 'BUCKAROO_SUCCESS';
    
    const BUCKAROO_FAILED            = 'BUCKAROO_FAILED';
    
    const BUCKAROO_ERROR             = 'BUCKAROO_ERROR';
    
    const BUCKAROO_NEUTRAL           = 'BUCKAROO_NEUTRAL';
    
    const BUCKAROO_PENDING_PAYMENT   = 'BUCKAROO_PENDING_PAYMENT';
    
    const BUCKAROO_INCORRECT_PAYMENT = 'BUCKAROO_INCORRECT_PAYMENT';
    
    const BUCKAROO_REJECTED          = 'BUCKAROO_REJECTED';
    
    /**
     * @var TIG_Buckaroo3Extended_Model_PaymentFee_Service
     */
    protected $_serviceModel;
    
    public function isAdmin()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return true;
        }
        
        if (Mage::getDesign()->getArea() == 'adminhtml') {
            return true;
        }
        
        return false;
    }
    
    public function log($message, $force = false)
    {
        Mage::log($message, Zend_Log::DEBUG, 'TIG_B3E.log', $force);
    }
    
    public function logException($e)
    {
        if (is_string($e)) {
            Mage::log($e, Zend_Log::ERR, 'TIG_B3E_Exception.log', true);
        } else {
            Mage::log($e->getMessage(), Zend_Log::ERR, 'TIG_B3E_Exception.log', true);
            Mage::log($e->getTraceAsString(), Zend_Log::ERR, 'TIG_B3E_Exception.log', true);
        }
    }
    
    public function isOneStepCheckout()
    {
        $moduleName = Mage::app()->getRequest()->getModuleName();
        
        if ($moduleName == 'onestepcheckout') {
            return true;
        }
        
        return false;
    }
    
    public function getFeeLabel($paymentMethodCode = false)
    {
        if ($paymentMethodCode) {
            $feeLabel = Mage::helper('buckaroo3extended')->__(
                Mage::getStoreConfig(
                    'buckaroo/' . $paymentMethodCode . '/payment_fee_label', Mage::app()->getStore()->getId()
                )
            );
            
            if (empty($feeLabel)) {
                $feeLabel = Mage::helper('buckaroo3extended')->__('Fee');
            }
        } else {
            $feeLabel = Mage::helper('buckaroo3extended')->__('Fee');
        }
        
        return $feeLabel;
    }
    
    public function resetBuckarooFeeInvoicedValues($order, $invoice)
    {
        $baseBuckarooFee    = $invoice->getBaseBuckarooFee();
        $paymentFee         = $invoice->getBuckarooFee();
        $baseBuckarooFeeTax = $invoice->getBaseBuckarooFeeTax();
        $paymentFeeTax      = $invoice->getBuckarooFeeTax();
        
        $baseBuckarooFeeInvoiced    = $order->getBaseBuckarooFeeInvoiced();
        $paymentFeeInvoiced         = $order->getBuckarooFeeInvoiced();
        $baseBuckarooFeeTaxInvoiced = $order->getBaseBuckarooFeeTaxInvoiced();
        $paymentFeeTaxInvoiced      = $order->getBuckarooFeeTaxInvoiced();
        
        if ($baseBuckarooFeeInvoiced && $baseBuckarooFee && $baseBuckarooFeeInvoiced >= $baseBuckarooFee) {
            $order->setBaseBuckarooFeeInvoiced($baseBuckarooFeeInvoiced - $baseBuckarooFee)
                  ->setBuckarooFeeInvoiced($paymentFeeInvoiced - $paymentFee)
                  ->setBaseBuckarooFeeTaxInvoiced($baseBuckarooFeeTaxInvoiced - $baseBuckarooFeeTax)
                  ->setBaseBuckarooFeeInvoiced($paymentFeeTaxInvoiced - $paymentFeeTax);
            $order->save();
        }
    }
    
    /**
     * Checks if the current edition of Magento is enterprise. Uses Mage::getEdition if available. If not, look for the
     * Enterprise_Enterprise extension. Finally, check the version number.
     *
     * @return boolean
     *
     */
    public function isEnterprise()
    {
        /**
         * Use Mage::getEdition, which is available since CE 1.7 and EE 1.12.
         */
        if (method_exists('Mage', 'getEdition')) {
            $edition = Mage::getEdition();
            if ($edition == Mage::EDITION_ENTERPRISE) {
                return true;
            }
            
            if ($edition == Mage::EDITION_COMMUNITY) {
                return false;
            }
        }
        
        /**
         * Check if the Enterprise_Enterprise extension is installed.
         */
        if (Mage::getConfig()->getNode('modules')->Enterprise_Enterprise) {
            return true;
        }
        
        return false;
    }
    
    public function getIsKlarnaEnabled()
    {
        return Mage::helper('core')->isModuleEnabled('Klarna_KlarnaPaymentModule');
    }
    
    /**
     * Checks if allowed countries, of globally all countries is required to fill in a region while checking out with
     * the paypal sellers protection enabled.
     * @return bool
     */
    public function checkRegionRequired()
    {
        $storeId       = Mage::app()->getRequest()->getParam('store');
        $allowSpecific = Mage::getStoreConfig('buckaroo/buckaroo3extended_paypal/allowspecific', $storeId);
        
        $regionRequiredCountries = array(
            'AR', //argentina
            'BR', //brazil
            'CA', //canada
            'CN', //china
            'ID', //indonesia
            'JP', //japan
            'MX', //mexico
            'TH', //thailand
            'US', //usa
        );
        
        if ($allowSpecific) {
            $allowedCountries = explode(
                ',', Mage::getStoreConfig('buckaroo/buckaroo3extended_paypal/specificcountry', $storeId)
            );
        } else {
            $allowedCountriesOptions = Mage::getModel('directory/country')->getResourceCollection()
                                           ->loadByStore($storeId)
                                           ->toOptionArray(true);
            
            $allowedCountries = array();
            foreach ($allowedCountriesOptions as $options) {
                $allowedCountries[] = $options['value'];
            }
        }
        
        //if one of the allowed countries in the required-region array exists
        //then the region must be required, if none exists then the message cannot be shown
        foreach ($regionRequiredCountries as $country) {
            if (in_array($country, $allowedCountries)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function checkSellersProtection($order)
    {
        if (!Mage::getStoreConfig('buckaroo/buckaroo3extended_paypal/active', $order->getStoreId())) {
            return false;
        }
        
        if (!Mage::getStoreConfig('buckaroo/buckaroo3extended_paypal/sellers_protection', $order->getStoreId())) {
            return false;
        }
        
        if ($order->getIsVirtual()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the Buckaroo fee label for a given store & paymentmethod
     *
     * @param null           $store
     * @param boolean|string $paymentMethod
     *
     * @return string
     */
    public function getBuckarooFeeLabel($store = null, $paymentMethod = false)
    {
        if ($store === null) {
            $store = Mage::app()->getStore();
        }
        
        if (!$paymentMethod) {
            return Mage::helper('buckaroo3extended')->__('Buckaroo Fee');
        }
        
        $xpath = 'buckaroo/' . $paymentMethod . '/payment_fee_label';
        $label = Mage::getStoreConfig($xpath, $store);
        
        return $label;
    }
    
    /**
     * Add Buckaroo fee tax info by updating an incorrect tax record.
     *
     * @param Mage_Sales_Model_Order $order
     * @param array                  $fullInfo
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
     * Add Buckaroo fee tax info by updating or adding a missing tax record.
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
         * Go through all tax records and add the Buckaroo fee tax to the entry that has the right title. If no entry
         * exists with that title, add it.
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
     * Add Buckaroo fee tax info by recreating the tax request.
     *
     * @param Mage_Sales_Model_Order                                           $order
     * @param array                                                            $fullInfo
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $source
     *
     * @return array
     */
    protected function _addBuckarooFeeTaxInfoFromRequest($order, $fullInfo, $source)
    {
        $store          = $order->getStore();
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
    
    /**
     * Alias for TIG_Buckaroo3Extended_Model_PaymentFee_Service::addBuckarooFeeTaxInfo()
     *
     * @param array                                                                                   $fullInfo
     * @param Mage_Sales_Model_Order|Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $source
     * @param Mage_Sales_Model_Order                                                                  $order
     *
     * @return array
     *
     * @see TIG_Buckaroo3Extended_Model_PaymentFee_Service::addBuckarooFeeTaxInfo()
     */
    public function addBuckarooFeeTaxInfo($fullInfo, $source, Mage_Sales_Model_Order $order)
    {
        $fullInfo = $this->getServiceModel()->addBuckarooFeeTaxInfo($fullInfo, $source, $order);
        
        return $fullInfo;
    }
    
    /**
     * @return TIG_Buckaroo3Extended_Model_PaymentFee_Service
     */
    public function getServiceModel()
    {
        if ($this->_serviceModel) {
            return $this->_serviceModel;
        }
        
        $serviceModel = Mage::getModel('buckaroo3extended/paymentFee_service');
        
        $this->setServiceModel($serviceModel);
        
        return $serviceModel;
    }
    
    /**
     * @param TIG_Buckaroo3Extended_Model_PaymentFee_Service $serviceModel
     *
     * @return $this
     */
    public function setServiceModel(TIG_Buckaroo3Extended_Model_PaymentFee_Service $serviceModel)
    {
        $this->_serviceModel = $serviceModel;
        
        return $this;
    }
    
    public function getNewStates($code = null, $order = null, $method = null)
    {
        $return = array(null, null);
        
        // Get the store Id
        $storeId = $order->getStoreId();
        
        $states = $this->setCurrentStates($storeId);
        
        // All three parameters need to be available
        if (!$code || !$order || !$method) {
            return $return;
        }
        
        // See if we even need to use custom statuses
        $useStatus = Mage::getStoreConfig('buckaroo/' . $method . '/active_status', $storeId);
        
        // Only look up custom statuses if we need to
        if ($useStatus) {
            $this->lookupCustomStates($states, $storeId, $method);
        }
        
        // Now see what
        // code we've been given and return the relevant bits
        switch ($code) {
            case self::BUCKAROO_SUCCESS:
                $return = array(
                    $states['success']['state'],
                    $states['success']['status'],
                );
                break;
            
            case self::BUCKAROO_ERROR:
            case self::BUCKAROO_FAILED:
                $return = array(
                    $states['failure']['state'],
                    $states['failure']['status'],
                );
                break;
            
            case self::BUCKAROO_PENDING_PAYMENT:
                $return = array(
                    $states['pending']['state'],
                    $states['pending']['status'],
                );
                break;
            
            case self::BUCKAROO_INCORRECT_PAYMENT:
                $return = array(
                    $states['incorrect']['state'],
                    $states['incorrect']['status'],
                );
                break;
        }
        
        return $return;
    }
    
    /**
     * @param int $storeId
     *
     * @return array
     */
    protected function setCurrentStates($storeId)
    {
        $states = array();
        
        // Get the states
        $states['success']['state']   = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_advanced/order_state_success', $storeId
        );
        $states['failure']['state']   = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_advanced/order_state_failed', $storeId
        );
        $states['pending']['state']   = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_advanced/order_state_pendingpayment', $storeId
        );
        $states['incorrect']['state'] = 'holded';
        
        // Get the default status values
        $states['success']['status']   = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_advanced/order_status_success', $storeId
        );
        $states['failure']['status']   = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_advanced/order_status_failed', $storeId
        );
        $states['pending']['status']   = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_advanced/order_status_pendingpayment', $storeId
        );
        $states['incorrect']['status'] = 'buckaroo_incorrect_payment';
        
        // Magento 1.4 compatibility
        if (version_compare(Mage::getVersion(), '1.5.0.0', '<')
            && version_compare(Mage::getVersion(), '1.4.0.0', '>')
        ) {
            $states['incorrect']['status'] = 'payment_review';
        }
        
        return $states;
    }
    
    /**
     * @param array $states
     * @param int   $storeId
     * @param       $method
     *
     * @return array
     */
    protected function lookupCustomStates($states, $storeId, $method)
    {
        $customSuccess = Mage::getStoreConfig('buckaroo/' . $method . '/order_status_success', $storeId);
        $customFailure = Mage::getStoreConfig('buckaroo/' . $method . '/order_status_failed', $storeId);
        $customPending = Mage::getStoreConfig('buckaroo/' . $method . '/order_status_pendingpayment', $storeId);
        
        if (!empty($customSuccess)) {
            $states['success']['status'] = $customSuccess;
        }
        
        if (!empty($customFailure)) {
            $states['failure']['status'] = $customFailure;
        }
        
        if (!empty($customPending)) {
            $states['pending']['status'] = $customPending;
        }
        
        return $states;
    }
    
    public function isApplePayInCartEnabled()
    {
        return $this->isApplePayEnabled('Cart',
            'buckaroo3extended/applepay/cart/button.phtml');
    }
    
    public function isApplePayInProductEnabled()
    {
        return $this->isApplePayEnabled('Product',
            'buckaroo3extended/applepay/cart/button.phtml');
    }
    
    private function isApplePayEnabled($page, $template)
    {
        $storeId = Mage::app()->getStore()->getStoreId();
    
        $buttons = explode(',', Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_applepay/buttons', $storeId
        ));
    
        return in_array($page, $buttons) ? $template : null;
    }
}
