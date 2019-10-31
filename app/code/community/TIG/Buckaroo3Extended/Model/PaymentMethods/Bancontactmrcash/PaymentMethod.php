<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Bancontactmrcash_PaymentMethod
    extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_bancontactmrcash';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_bancontactmrcash_checkout_form';

    protected $_canRefundInvoicePartial = true;
}
