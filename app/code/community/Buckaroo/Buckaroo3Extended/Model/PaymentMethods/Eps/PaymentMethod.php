<?php
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Eps_PaymentMethod extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_eps';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_eps_checkout_form';
}
