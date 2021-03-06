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
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Applepay_PaymentMethod extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'ARS',
        'AUD',
        'BRL',
        'CAD',
        'CHF',
        'CNY',
        'CZK',
        'DKK',
        'EUR',
        'GBP',
        'HRK',
        'ISK',
        'JPY',
        'LTL',
        'LVL',
        'MXN',
        'NOK',
        'NZD',
        'PLN',
        'RUB',
        'SEK',
        'TRY',
        'USD',
        'ZAR',
    );

    protected $_code = 'buckaroo3extended_applepay';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_applepay_checkout_form';

    public function validate()
    {
        $postData = Mage::app()->getRequest()->getPost();

        $paymentData = null;
        if (isset($postData['apple-pay-response'])) {
            $paymentData = $postData['apple-pay-response'];
        }

        if (!$paymentData && isset($postData['payment']['token']['paymentData'])) {
            $paymentData = json_encode(array('paymentData' => $postData['payment']['token']['paymentData']));
        }

        $billingContact = null;
        if (isset($postData['payment']['billingContact'])) {
            $billingContact = json_encode($postData['payment']['billingContact']);
        }

        if ($paymentData) {
            $this->getInfoInstance()->setAdditionalInformation(
                array(
                    $this->_code . '_response' => $paymentData,
                    $this->_code . '_billingContact' => $billingContact
                )
            );
        }

        return parent::validate();
    }
}
