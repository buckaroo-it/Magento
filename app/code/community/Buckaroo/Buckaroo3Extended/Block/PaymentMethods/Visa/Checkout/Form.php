<?php
class Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Visa_Checkout_Form extends
    Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/visa/checkout/form.phtml');
        parent::_construct();
    }
}
