<?php
/**  ____________  _     _ _ ________  ___  _ _  _______   ___  ___  _  _ _ ___
 *   \_ _/ \_ _/ \| |   |_| \ \_ _/  \| _ || \ |/  \_ _/  / __\| _ |/ \| | | _ \
 *    | | | | | ' | |_  | |   || | '_/|   /|   | '_/| |  | |_ \|   / | | | | __/
 *    |_|\_/|_|_|_|___| |_|_\_||_|\__/|_\_\|_\_|\__/|_|   \___/|_\_\\_/|___|_|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

class TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay_PaymentMethod
    extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_afterpay';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_afterpay_checkout_form';

    protected $_canOrder                = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;

    /**
     * {@inheritdoc}
     */
    public function canCapture()
    {
        if ($this->getConfigPaymentAction() == Mage_Payment_Model_Method_Abstract::ACTION_ORDER) {
            return false;
        }

        return $this->_canCapture;
    }

    /**
     * {@inheritdoc}
     */
    // @codingStandardsIgnoreLine
    public function capture(Varien_Object $payment, $amount)
    {
        if (!$this->canCapture()) {
            Mage::throwException(Mage::helper('payment')->__('Capture action is not available.'));
        }

        /** @var TIG_Buckaroo3Extended_Model_Request_Capture $captureRequest */
        $captureRequest = Mage::getModel(
            'buckaroo3extended/request_capture',
            array(
                'payment' => $payment
            )
        );

        try {
            $captureRequest->sendRequest();
        } catch (Exception $e) {
            Mage::helper('buckaroo3extended')->logException($e);
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    /**
     * @param $post
     *
     * @return array
     */
    protected function _getBPEPostData($post)
    {
        $accountNumber = isset($post[$this->_code . '_bpe_customer_account_number'])
            ? $post[$this->_code . '_bpe_customer_account_number'] : '';

        $customerBirthDate = date(
            'Y-m-d', strtotime(
                $post['payment'][$this->_code]['year']
                . '-' . $post['payment'][$this->_code]['month']
                . '-' . $post['payment'][$this->_code]['day']
            )
        );

        $array = array(
            'BPE_Customergender'    => $post[$this->_code . '_BPE_Customergender'],
            'BPE_AccountNumber'     => $this->filterAccount($accountNumber),
            'BPE_PhoneNumber'       => $post[$this->_code . '_bpe_customer_phone_number'],
            'BPE_customerbirthdate' => $customerBirthDate,
            'BPE_B2B'               => (int)$post[$this->_code . '_BPE_BusinessSelect'],
            'BPE_Accept'            => 'true',
        );

        if ((int)$array['BPE_B2B'] == 2) {
            $additionalArray = array(
                'BPE_CompanyCOCRegistration' => $post[$this->_code . '_BPE_CompanyCOCRegistration'],
                'BPE_CompanyName'            => $post[$this->_code . '_BPE_CompanyName'],
            );

            $array = array_merge($array, $additionalArray);
        }

        return $array;
    }

    public function getOrderPlaceRedirectUrl()
    {
        $session = Mage::getSingleton('checkout/session');

        $post = Mage::app()->getRequest()->getPost();

        $array = $this->_getBPEPostData($post);

        $session->setData('additionalFields', $array);

        return parent::getOrderPlaceRedirectUrl();
    }

    public function validate()
    {
        $postData = Mage::app()->getRequest()->getPost();
        if (!array_key_exists($this->_code . '_bpe_accept', $postData)
            || $postData[$this->_code . '_bpe_accept'] != 'checked'
        ) {
            Mage::throwException(
                Mage::helper('buckaroo3extended')->__('Please accept the terms and conditions.')
            );
        }

        $this->getInfoInstance()->setAdditionalInformation('checked_terms_and_conditions', true);

        $BPEArray = $this->_getBPEPostData($postData);

        foreach ($BPEArray as $key => $value) {
            $this->getInfoInstance()->setAdditionalInformation($key, $value);
        }

        return parent::validate();
    }

    /**
     * @param null | Mage_Sales_Model_Quote $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $storeId = Mage::app()->getStore()->getId();

        if (!$this->checkQuoteAndSession($quote, $storeId)) {
            return false;
        }

        if (!$this->checkAvailabilityInShopArea($storeId)) {
            return false;
        }

        if (!$this->checkWithinAmount($storeId, $quote)) {
            return false;
        }

        // Check limit by ip
        if (mage::getStoreConfig('dev/restrict/allow_ips')
            && Mage::getStoreConfig('buckaroo/' . $this->_code . '/limit_by_ip')) {
            $allowedIp = explode(',', mage::getStoreConfig('dev/restrict/allow_ips'));
            if (!in_array(Mage::helper('core/http')->getRemoteAddr(), $allowedIp)) {
                return false;
            }
        }

        // Get current currency code
        $currency = Mage::app()->getStore()->getBaseCurrencyCode();

        // Currency is not available for this module
        if (!in_array($currency, $this->allowedCurrencies)) {
            return false;
        }

        //Afterpay B2B cannot be used when ordering with a BE address
        $paymethod = Mage::getStoreConfig('buckaroo/' . $this->_code . '/paymethod', $storeId);
        $business = Mage::getStoreConfig('buckaroo/' . $this->_code . '/business', $storeId);
        if ($quote
            && $paymethod == 'afterpaydigiaccept'
            && $business == '2'
            && $quote->getBillingAddress()->getCountry() == 'BE'
        ) {
            return false;
        }

        if ($this->hideForPosPayment()) {
            return false;
        }

        $canUseBuckaroo = TIG_Buckaroo3Extended_Model_Request_Availability::canUseBuckaroo($quote);

        return $canUseBuckaroo;
    }

    protected function checkQuoteAndSession($quote, $storeId)
    {
        // Check if quote is null, and try to look it up based on adminhtml session
        if (!$quote && Mage::helper('buckaroo3extended')->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote');
        }

        // If quote is not null, set storeId to quote storeId
        if ($quote) {
            $storeId = $quote->getStoreId();
        }

        //check if the module is set to enabled
        if (!Mage::getStoreConfig('buckaroo/' . $this->_code . '/active', $storeId)) {
            return false;
        }

        if ($quote) {
            $quoteItems = $quote->getAllVisibleItems();
            if (count($quoteItems) > 99) {
                return false;
            }
        }

        $session = Mage::getSingleton('checkout/session');
        if ($session->getData('buckarooAfterpayRejected') == true) {
            return false;
        }

        // Check if the country specified in the billing address is allowed to use this payment method
        if ($quote
            && Mage::getStoreConfig('buckaroo/' . $this->_code . '/allowspecific', $storeId) == 1
            && $quote->getBillingAddress()->getCountry()
        ) {
            $allowedCountries = explode(
                ',', Mage::getStoreConfig('buckaroo/' . $this->_code . '/specificcountry', $storeId)
            );
            $country = $quote->getBillingAddress()->getCountry();

            if (!in_array($country, $allowedCountries)) {
                return false;
            }
        }

        return true;
    }

    protected function checkAvailabilityInShopArea($storeId)
    {
        $areaAllowed = null;
        if ($this->canUseInternal()) {
            $areaAllowed = Mage::getStoreConfig('buckaroo/' . $this->_code . '/area', $storeId);
        }

        // Check if the paymentmethod is available in the current shop area (frontend or backend)
        if ($areaAllowed == 'backend'
            && !Mage::helper('buckaroo3extended')->isAdmin()
        ) {
            return false;
        } elseif ($areaAllowed == 'frontend'
            && Mage::helper('buckaroo3extended')->isAdmin()
        ) {
            return false;
        }

        return true;
    }

    protected function checkWithinAmount($storeId, $quote)
    {
        // Check if max amount for the issued PaymentMethod is set and if the quote basegrandtotal exceeds that
        $maxAmount = Mage::getStoreConfig('buckaroo/' . $this->_code . '/max_amount', $storeId);
        if ($quote
            && !empty($maxAmount)
            && $quote->getBaseGrandTotal() > $maxAmount
        ) {
            return false;
        }

        // check if min amount for the issued PaymentMethod is set and if the quote basegrandtotal is less than that
        $minAmount = Mage::getStoreConfig('buckaroo/' . $this->_code . '/min_amount', $storeId);
        if ($quote
            && !empty($minAmount)
            && $quote->getBaseGrandTotal() < $minAmount
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param $responseData
     *
     * @return bool|string
     */
    public function getRejectedMessage($responseData)
    {
        // @codingStandardsIgnoreLine
        if (!isset($responseData->ConsumerMessage->HtmlText)) {
            return false;
        }

        // @codingStandardsIgnoreLine
        $rejectedMessage = $responseData->ConsumerMessage->HtmlText;

        if (!$rejectedMessage) {
            return false;
        }

        return $rejectedMessage;
    }
}
