<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Visaelectron_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
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

    protected $_code = 'buckaroo3extended_visaelectron';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_visaelectron_checkout_form';
}
