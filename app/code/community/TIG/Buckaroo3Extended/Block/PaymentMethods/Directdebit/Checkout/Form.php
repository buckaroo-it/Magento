<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Directdebit_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/directdebit/checkout/form.phtml');
        parent::_construct();
    }

    public function getAccountOwner()
    {
        $accountHolder = $this->getSession()->getData('payment[account_owner]');

        if (!$accountHolder) {
            $accountHolder = $this->getName();
        }

        return $accountHolder;
    }

    public function getAccountNumber()
    {
        return $this->getSession()->getData('payment[account_number]');
    }

    public function getBankNumber()
    {
        return $this->getSession()->getData('payment[bank_number]');
    }
}
