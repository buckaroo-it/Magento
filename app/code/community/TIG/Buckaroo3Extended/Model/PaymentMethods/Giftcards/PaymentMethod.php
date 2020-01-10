<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Giftcards_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_giftcards';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_giftcards_checkout_form';

    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
}
