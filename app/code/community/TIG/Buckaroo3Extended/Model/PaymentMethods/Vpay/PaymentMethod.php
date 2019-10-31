<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Vpay_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
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
        'HUF',
        'LTL',
        'LVL',
        'MXN',
        'NOK',
        'PLN',
        'SEK',
        'TRY',
        'USD',
    );

    protected $_code = 'buckaroo3extended_vpay';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_vpay_checkout_form';
}
