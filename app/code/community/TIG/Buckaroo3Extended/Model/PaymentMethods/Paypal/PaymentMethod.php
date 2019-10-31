<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Paypal_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'AUD',
        'BRL',
        'CAD',
        'CHF',
        'CZK',
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

    protected $_code = 'buckaroo3extended_paypal';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_paypal_checkout_form';

    protected $_orderMailStatusses      = array( TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS, TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_PENDING_PAYMENT);

}
