<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Onlinegiro_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/onlinegiro/checkout/form.phtml');
        parent::_construct();
    }
}
