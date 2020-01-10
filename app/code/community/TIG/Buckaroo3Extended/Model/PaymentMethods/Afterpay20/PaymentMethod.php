<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
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
class TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_PaymentMethod
    extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_afterpay20';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_afterpay20_checkout_form';

    protected $_canOrder                = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;

    /** @var bool TODO: Set to true and implement Authorize flow when it is available in the API */
    protected $_canCapture        = false;
    protected $_canCapturePartial = false;

    /**
     * @param array $post
     *
     * @return array
     */
    protected function _getBPEPostData($post)
    {
        $customerBirthDate = null;
        $dobPostData = $post['payment'][$this->_code];

        if (isset($dobPostData['day']) && isset($dobPostData['month']) && isset($dobPostData['year'])) {
            $customerBirthDate = date(
                'd-m-Y',
                strtotime($dobPostData['day'] . '-' . $dobPostData['month'] . '-' . $dobPostData['year'])
            );
        }

        $array = array(
            'BPE_Customergender'    => $post[$this->_code . '_BPE_Customergender'],
            'BPE_PhoneNumber'       => $post[$this->_code . '_bpe_customer_phone_number'],
            'BPE_customerbirthdate' => $customerBirthDate,
            'identification_number' => $post[$this->_code . '_bpe_customer_idnumber'],
            'BPE_Accept'            => 'true',
        );

        return $array;
    }

    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $session = Mage::getSingleton('checkout/session');

        $post = Mage::app()->getRequest()->getPost();

        $array = $this->_getBPEPostData($post);

        $session->setData('additionalFields', $array);

        return parent::getOrderPlaceRedirectUrl();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Mage_Core_Exception
     */
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
     * {@inheritdoc}
     */
    public function isAvailable($quote = null)
    {
        if (!$quote && Mage::helper('buckaroo3extended')->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote');
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

        return parent::isAvailable($quote);
    }

    /**
     * @param $responseData
     *
     * @return bool|string
     */
    public function getRejectedMessage($responseData)
    {
        // @codingStandardsIgnoreLine
        if (!isset($responseData->Status->SubCode->_)) {
            return false;
        }

        // @codingStandardsIgnoreLine
        $rejectedMessage = $responseData->Status->SubCode->_;

        if (!$rejectedMessage) {
            return false;
        }

        return $rejectedMessage;
    }
}
