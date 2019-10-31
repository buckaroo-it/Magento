<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Giftcards_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/giftcards/checkout/form.phtml');
        parent::_construct();
    }
}
