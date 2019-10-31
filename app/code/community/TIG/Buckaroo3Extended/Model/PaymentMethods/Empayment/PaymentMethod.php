<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Empayment_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_empayment';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_empayment_checkout_form';

    public function getOrderPlaceRedirectUrl()
    {
        return parent::getOrderPlaceRedirectUrl();
    }
}
