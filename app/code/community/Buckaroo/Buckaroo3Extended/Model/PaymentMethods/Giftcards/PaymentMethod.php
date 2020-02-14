<?php
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Giftcards_PaymentMethod extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_giftcards';

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_giftcards_checkout_form';

    /**
     * @return bool
     */
    public function canRefund()
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $this->getInfoInstance();

        $transactions = $payment->getAdditionalInformation('transactions');

        /** @var $transactionManager Buckaroo_Buckaroo3Extended_Model_TransactionManager */
        $transactionManager = Mage::getModel('buckaroo3extended/transactionManager');
        $transactionManager->setTransactionArray($transactions);

        if ($transactionManager->getPossibleRefundAmount() !== false) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function canRefundInvoicePartial()
    {
        return $this->canRefund();
    }

    /**
     * @param array $post
     *
     * @return array
     */
    protected function _getPostData($post)
    {
        $array = [
            'currentgiftcard' => $post['payment']['currentgiftcard']
        ];
        return $array;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Mage_Core_Exception
     */
    public function validate()
    {
        $postData = Mage::app()->getRequest()->getPost();

        $postArray = $this->_getPostData($postData);
        foreach ($postArray as $key => $value) {
            $this->getInfoInstance()->setAdditionalInformation($key, $value);
        }

        return parent::validate();
    }
}
