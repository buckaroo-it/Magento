<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Transfer_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'CHF',
        'CNY',
        'CZK',
        'DKK',
        'EUR',
        'GBP',
        'JPY',
        'NOK',
        'PLN',
        'SEK',
        'USD',
    );

    protected $_code = 'buckaroo3extended_transfer';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_transfer_checkout_form';

    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_orderMailStatusses      = array( TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS, TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_PENDING_PAYMENT);

    public function getOrderPlaceRedirectUrl()
    {
        $session = Mage::getSingleton('checkout/session');

        $post = Mage::app()->getRequest()->getPost();

        $customerBirthDate = date(
            'Y-m-d', strtotime(
                $post['payment'][$this->_code]['year']
                . '-' . $post['payment'][$this->_code]['month']
                . '-' . $post['payment'][$this->_code]['day']
            )
        );

        if (isset($post[$this->_code.'_BPE_Customergender'])) {
            $session->setData(
                'additionalFields', array(
                'BPE_Customergender'    => $post[$this->_code.'_BPE_Customergender'],
                'BPE_Customermail'      => $post[$this->_code.'_BPE_Customermail'],
                'BPE_customerbirthdate' => $customerBirthDate
                )
            );
        }

        return parent::getOrderPlaceRedirectUrl();
    }
}
