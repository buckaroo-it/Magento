<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Applepay_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    /**
     * TIG_Buckaroo3Extended_Block_PaymentMethods_Applepay_Checkout_Form constructor.
     */
    public function _construct()
    {
        $this->setTemplate('buckaroo3extended/applepay/checkout/form.phtml');
        parent::_construct();
    }
}
