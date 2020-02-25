<?php
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Trustly_PaymentMethod extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
        'GBP',
        'PLN',
        'SEK',
        'DKK',
        'NOK',
    );
    //http://dev.buckaroo.nl/PaymentMethods/Description/trustly#top

    protected $_code = 'buckaroo3extended_trustly';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_trustly_checkout_form';

    protected $_orderMailStatusses      = array( Buckaroo_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS, Buckaroo_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_PENDING_PAYMENT);
}
