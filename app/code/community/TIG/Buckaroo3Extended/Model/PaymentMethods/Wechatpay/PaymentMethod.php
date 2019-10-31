<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Wechatpay_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
        'USD',
        'GBP',
        'HKD',
        'JPY',
        'CAD',
        'AUD',
        'NZD',
        'KRW',
        'THB',
        'SGD',
        'RUB',
    );

    protected $_code = 'buckaroo3extended_wechatpay';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_wechatpay_checkout_form';

    protected $_orderMailStatusses      = array( TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS, TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_PENDING_PAYMENT);

}
