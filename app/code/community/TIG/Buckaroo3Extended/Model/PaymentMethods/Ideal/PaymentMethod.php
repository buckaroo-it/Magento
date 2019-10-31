<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Ideal_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_ideal';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_ideal_checkout_form';

    public function getOrderPlaceRedirectUrl()
    {
        $session = Mage::getSingleton('checkout/session');

        $postData = Mage::app()->getRequest()->getPost();

        if(isset($postData[$this->_code.'_BPE_Issuer']))
        {
            $session->setData('additionalFields', array('Issuer' => $postData[$this->_code.'_BPE_Issuer']));
        }

        return parent::getOrderPlaceRedirectUrl();
    }
}
