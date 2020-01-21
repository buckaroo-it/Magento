<?php
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Sofortueberweisung_PaymentMethod extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
        'PLN',
    );

    protected $_code = 'buckaroo3extended_sofortueberweisung';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_sofortueberweisung_checkout_form';

    protected $_orderMailStatusses      = array( Buckaroo_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS, Buckaroo_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_PENDING_PAYMENT);

}
