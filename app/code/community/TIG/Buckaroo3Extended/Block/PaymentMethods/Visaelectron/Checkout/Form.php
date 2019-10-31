<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Visaelectron_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/visaelectron/checkout/form.phtml');
        parent::_construct();
    }
}
