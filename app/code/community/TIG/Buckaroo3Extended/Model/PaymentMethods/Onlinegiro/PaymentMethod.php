<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Onlinegiro_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_onlinegiro';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_onlinegiro_checkout_form';

    protected $_canUseInternal = true;

    public function assignData($data)
    {
        if (!Mage::helper('buckaroo3extended')->isAdmin()) {
            $session = Mage::getSingleton('checkout/session');
        } else {
            $session = Mage::getSingleton('core/session');
        }

        $postData = Mage::app()->getRequest()->getPost();

        $session->setData(
            'additionalFields', array(
            'gender'    => $postData['buckaroo3extended_onlinegiro_BPE_Customergender'],
            'firstname' => $postData['buckaroo3extended_onlinegiro_BPE_Customerfirstname'],
            'lastname'  => $postData['buckaroo3extended_onlinegiro_BPE_Customerlastname'],
            'mail'      => $postData['buckaroo3extended_onlinegiro_BPE_Customermail'],
            )
        );

        return parent::assignData($data);
    }
}
