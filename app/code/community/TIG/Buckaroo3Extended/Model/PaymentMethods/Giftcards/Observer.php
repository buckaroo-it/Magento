<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Giftcards_Observer extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code = 'buckaroo3extended_giftcards';
    protected $_method = 'giftcards';

    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $vars = $request->getVars();

        $array = array();
        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_' .  $this->_method . '/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
            $array['creditmanagement'] = array(
                    'action'    => 'Invoice',
                    'version'   => 1,
            );
        }

        if (array_key_exists('services', $vars) && is_array($vars['services'])) {
            $vars['services'] = array_merge($vars['services'], $array);
        } else {
            $vars['services'] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    public function buckaroo3extended_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();
        $vars = $request->getVars();

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_' . $this->_method . '/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
            $this->_addCustomerVariables($vars);
            $this->_addCreditManagement($vars);
            $this->_addAdditionalCreditManagementVariables($vars);
        }

        $availableCards = Mage::getStoreConfig('buckaroo/buckaroo3extended_giftcards/cards_allowed', Mage::app()->getStore()->getId());
        if (empty($availableCards)) {
            Mage::throwException('no giftcards available');
        }

        $availableCards .= ',ideal';

        $array = array(
                'servicesSelectableByClient' => $availableCards,
                'continueOnImcomplete'       => 'RedirectToHTML',
        );

        if (array_key_exists('customVars', $vars)) {
            $vars['customVars'] = array_merge($vars['customVars'], $array);
        } else {
            $vars['customVars'] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    public function buckaroo3extended_request_setmethod(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $codeBits = explode('_', $this->_code);
        $code = end($codeBits);
        $request->setMethod($code);

        return $this;
    }

    protected function _isChosenMethod($observer)
    {
        $ret = false;

        if (null === $observer->getOrder()) {
            return false;
        }

        $chosenMethod = $observer->getOrder()->getPayment()->getMethod();

        if ($chosenMethod === $this->_code) {
            $ret = true;
        }

        return $ret;
    }

    public function buckaroo3extended_push_custom_processing($observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $push = $observer->getPush();
        $postData = $push->getPostArray();
        $order = $observer->getOrder();


        // Add transaction to transactionManager for managing (partial) refunds
        // with different payment methods
        if (!empty($postData['brq_mutationtype']) &&
            $postData['brq_mutationtype'] == 'Processing'
        ) {
            $payment = $order->getPayment();
            $transactions = $payment->getAdditionalInformation('transactions');

            /** @var $transactionManager TIG_Buckaroo3Extended_Model_TransactionManager */
            $transactionManager = Mage::getModel('buckaroo3extended/transactionManager');
            $transactionManager->setTransactionArray($transactions);

            $transactionKey = $postData['brq_transactions'];
            $amount = $postData['brq_amount'];
            $method = $postData['brq_transaction_method'];

            $transactions = $transactionManager->addDebitTransaction($transactionKey, $amount, $method);

            $payment->setAdditionalInformation('transactions', $transactions);
            $payment->save();
        }

        //Partial payment
        if (!empty($postData['brq_relatedtransaction_partialpayment'])) {
            if ($postData['brq_amount'] < $order->getGrandTotal()) {

                $order->setTransactionKey($postData['brq_relatedtransaction_partialpayment']);

                $processingPaymentStatus  = Mage::getStoreConfig('buckaroo/buckaroo3extended_giftcards/order_status_giftcard', $order->getStoreId());
                if (!empty($processingPaymentStatus)) {
                    $order->setStatus($processingPaymentStatus);
                }

                $order->save();
                $push->setCustomResponseProcessing(true);
            }
        }
    }


    public function buckaroo3extended_return_custom_processing($observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $return = $observer->getReturn();
        $postData = $return->getPostArray();
        $order = $observer->getOrder();
        $code = $order->getPayment()->getMethodInstance()->getCode();
        if ($code == 'buckaroo3extended_giftcards' && $postData['brq_statuscode'] == 190 && $postData['brq_amount'] < $order->getGrandTotal()){
            $return->setCustomResponseProcessing(true);
            $return->customSuccess();
        }

        if ($code == 'buckaroo3extended_giftcards' && $postData['brq_statuscode'] == 890 && $postData['brq_amount'] < $order->getGrandTotal()) {
            $return->setCustomResponseProcessing(true);
            $return->customFailed();
        }
    }

    public function buckaroo3extended_refund_request_setmethod(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $codeBits = explode('_', $this->_code);
        $code = end($codeBits);
        $request->setMethod($code);

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_refund_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();
        $this->_order = $request->getOrder();
        $vars = $request->getVars();
        $_method = $this->getMethod();


        $array = array(
            'action' => 'Refund',
            'version' => 1
        );

        if (array_key_exists('services', $vars) && is_array($vars['services'][$_method])) {
            $vars['services'][$_method] = array_merge($vars['services'][$_method], $array);
        } else {
            $vars['services'][$_method] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_refund_request_addcustomvars(Varien_Event_Observer $observer)
    {
        return $this;
    }

}
