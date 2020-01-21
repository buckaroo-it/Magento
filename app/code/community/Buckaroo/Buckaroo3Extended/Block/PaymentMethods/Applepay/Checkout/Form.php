<?php
class Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Applepay_Checkout_Form
    extends Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    /**
     * Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Applepay_Checkout_Form constructor.
     */
    public function _construct()
    {
        $this->setTemplate('buckaroo3extended/applepay/checkout/form.phtml');
        parent::_construct();
    }
}
