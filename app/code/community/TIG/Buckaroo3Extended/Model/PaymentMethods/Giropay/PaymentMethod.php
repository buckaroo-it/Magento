<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Giropay_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_giropay';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_giropay_checkout_form';

    public function getOrderPlaceRedirectUrl()
    {
        $session = Mage::getSingleton('checkout/session');

        $postData = Mage::app()->getRequest()->getPost();

        if(isset($postData[$this->_code.'_BPE_Bic']))
        {
            $session->setData(
                'additionalFields', array(
                'bic' => $postData[$this->_code.'_BPE_Bic'],
                )
            );
        }

        return Mage::getUrl('buckaroo3extended/checkout/checkout', array('_secure' => true, 'method' => $this->_code));
    }
}
