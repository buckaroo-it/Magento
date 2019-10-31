<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Giftcards_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
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

        /** @var $transactionManager TIG_Buckaroo3Extended_Model_TransactionManager */
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
}
