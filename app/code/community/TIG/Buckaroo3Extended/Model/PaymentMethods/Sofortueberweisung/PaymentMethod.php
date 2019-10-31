<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Sofortueberweisung_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
        'PLN',
    );

    protected $_code = 'buckaroo3extended_sofortueberweisung';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_sofortueberweisung_checkout_form';

    protected $_orderMailStatusses      = array( TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS, TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_PENDING_PAYMENT);

}
