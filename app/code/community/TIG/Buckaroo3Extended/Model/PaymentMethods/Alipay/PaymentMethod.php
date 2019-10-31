<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Alipay_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
        'USD',
        'JPY',
        'GBP',
        'CAD',
        'AUD',
        'SGD',
        'CHF',
        'SEK',
        'DKK',
        'NOK',
        'NZD',
        'THB',
        'HKD'
    );
    //https://intl.alipay.com/open/product-detail.htm?bizCode=OPEN_ONLINE_PAYMENT-Website

    protected $_code = 'buckaroo3extended_alipay';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_alipay_checkout_form';

    protected $_orderMailStatusses      = array( TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS, TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_PENDING_PAYMENT);

}
