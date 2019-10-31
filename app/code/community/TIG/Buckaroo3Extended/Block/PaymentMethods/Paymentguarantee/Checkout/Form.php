<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Paymentguarantee_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    protected function _construct()
    {
        $this->setTemplate('buckaroo3extended/paymentguarantee/checkout/form.phtml');
        parent::_construct();
    }
}
