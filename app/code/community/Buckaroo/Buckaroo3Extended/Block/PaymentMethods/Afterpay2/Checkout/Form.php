<?php
class Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Afterpay2_Checkout_Form
      extends Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Afterpay_Checkout_Form
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/afterpay2/checkout/form.phtml');
        parent::_construct();
    }
}
