<?php
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Wechatpay_PaymentMethod extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
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

    protected $_orderMailStatusses      = array( Buckaroo_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS, Buckaroo_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_PENDING_PAYMENT);

}
