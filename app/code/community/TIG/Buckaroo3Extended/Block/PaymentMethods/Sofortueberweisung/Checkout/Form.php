<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Sofortueberweisung_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/sofortueberweisung/checkout/form.phtml');
        parent::_construct();
    }
}
