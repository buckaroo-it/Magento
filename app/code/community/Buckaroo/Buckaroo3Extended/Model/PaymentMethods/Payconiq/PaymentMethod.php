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
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_PaymentMethod extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'AUD',
        'BRL',
        'CAD',
        'CHF',
        'DKK',
        'EUR',
        'GBP',
        'HKD',
        'HUF',
        'ILS',
        'JPY',
        'MYR',
        'NOK',
        'NZD',
        'PHP',
        'PLN',
        'SEK',
        'SGD',
        'THB',
        'TRY',
        'TWD',
        'USD',
    );

    protected $_code = 'buckaroo3extended_payconiq';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_payconiq_checkout_form';

    /**
     * {@inheritdoc}
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('buckaroo3extended/payconiq/checkout', array('_secure' => true, 'method' => $this->_code));
    }
}
