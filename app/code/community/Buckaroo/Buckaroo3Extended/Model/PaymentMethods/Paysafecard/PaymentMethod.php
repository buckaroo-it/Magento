<?php
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Paysafecard_PaymentMethod extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_paysafecard';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_paysafecard_checkout_form';

    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
}
