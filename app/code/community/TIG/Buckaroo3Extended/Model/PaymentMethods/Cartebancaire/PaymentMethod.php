<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Cartebancaire_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_cartebancaire';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_cartebancaire_checkout_form';
}
