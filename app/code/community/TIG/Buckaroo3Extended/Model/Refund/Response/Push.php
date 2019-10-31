<?php
class TIG_Buckaroo3Extended_Model_Refund_Response_Push extends TIG_Buckaroo3Extended_Model_Refund_Response_Abstract
{
    const PAYMENTCODE = 'buckaroo3extended';

    protected $_order = '';
    protected $_creditmemo = '';
    protected $_postArray = '';
    protected $_debugEmail = '';
    protected $_method = '';
    protected $_storeId = '';

    public function setCurrentOrder($order)
    {
        $this->_order = $order;
    }

    public function getCurrentOrder()
    {
        return $this->_order;
    }

    public function setCreditmemo($creditmemo)
    {
        $this->_creditmemo = $creditmemo;
    }

    public function getCreditmemo()
    {
        return $this->_creditmemo;
    }

    public function setPostArray($array)
    {
        $this->_postArray = $array;
    }

    public function getPostArray()
    {
        return $this->_postArray;
    }

    public function setDebugEmail($debugEmail)
    {
        $this->_debugEmail = $debugEmail;
    }

    public function getDebugEmail()
    {
        return $this->_debugEmail;
    }

    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
    }

    public function getStoreId()
    {
        return $this->_storeId;
    }

    public function __construct($data = array())
    {
        $this->setCurrentOrder($data['order']);
        $this->setPostArray($data['postArray']);
        $this->setDebugEmail($data['debugEmail']);
        $this->setStoreId($this->getOrder()->getStoreId());

        foreach ($data['order']->getCreditmemosCollection() as $creditmemo)
        {
            if ($creditmemo->getTransactionKey() == $data['postArray']['brq_transactions']) {
                $this->setCreditmemo($creditmemo);
                break;
            }
        }

        if (empty($creditmemo)) {
            $this->_debugEmail .= "Could not locate a creditmemo with the supplied transaction key. \n";
        }
    }
    /**
     * Processes 'pushes' receives from Buckaroo with the purpose of updating an existing creditmemo or create a new one.
     *
     * @return boolean
     */
    public function processPush()
    {
        //check if the push is valid and if the order can be updated
        $canProcessPush = $this->_canProcessRefundPush();
        list($canProcess, $canUpdate) = $canProcessPush;

        $this->_debugEmail .= "can the creditmemo be processed? " . $canProcess . "\ncan the creditmemo be updated? " . $canUpdate . "\n";

        if (!$canProcess || !$canUpdate) {
            return false;
        }

        $response = $this->_parseRefundPostResponse($this->_postArray['brq_statuscode']);

        $this->_debugEmail .= "Response received: " . var_export($response, true) . "\n\n";

        Mage::dispatchEvent('buckaroo3extended_refund_push_custom_processing', array('push' => $this, 'order' => $this->getCurrentOrder(), 'response' => $response));

        if ($this->getCustomResponseProcessing()) {
            return true;
        }

        return true;
    }

    /**
     * Checks if the post received is valid by checking its signature field.
     * This field is unique for every payment and every store.
     * Also calls method that checks if an order is able to be updated further.
     * Canceled, completed, holded etc. orders are not able to be updated
     *
     * @return array $return
     */
    protected function _canProcessRefundPush()
    {
        $correctSignature = false;
        $canUpdate = false;
        $signature = $this->_calculateSignature();
        if ($signature === $this->_postArray['brq_signature']) {
            $correctSignature = true;
        }

        //check if the order can receive further status updates
        if ($correctSignature === true) {
            if ($this->_order->canRefund() && $this->_postArray['brq_statuscode'] == '190') {
                $canUpdate = true;
            }
        }

        $return = array(
            (bool) $correctSignature,
            (bool) $canUpdate,
        );
        return $return;
    }

    /**
     * Process a succesful order. Sets its new state and status, sends an order confirmation email
     * and creates an invoice if set in config.
     *
     * @TODO $trx will be used for Buckaroo2012Refund, to be added in 3.0.0
     *
     * @param array $response | int $response
     * @param string $description
     *
     * @return boolean
     */
    protected function _processSuccess($newStates, $description = false)
    {
        $this->_autoInvoice();

        $description = Mage::helper('buckaroo3extended')->__($description);

        $description .= " (#{$this->_postArray['brq_statuscode']})";

        //sets the transaction key if its defined ($trx)
        //will retrieve it from the response array, if response actually is an array
        if (!$this->_order->getTransactionKey() && array_key_exists('brq_transactions', $this->_postArray)) {
            $this->_order->setTransactionKey($this->_postArray['brq_transactions']);
            $this->_order->save();
        }

        $this->_order->setState($newStates[0], $newStates[1], $description)
                     ->save();

        //send new order email if it hasnt already been sent
        if(!$this->_order->getEmailSent())
        {
            $this->_order->sendNewOrderEmail();
        }

        return true;
    }

    /**
     * Process a failed order. Sets its new state and status and cencels the order
     * if set in config.
     *
     * @param array $newStates
     * @param string $description
     *
     * @return boolean
     */
    protected function _processFailed($newStates, $description = false)
    {
        $description .= " (#{$this->_postArray['brq_statuscode']})";

        //sets the transaction key if its defined ($trx)
        //will retrieve it from the response array, if response actually is an array
        if (!$this->_order->getTransactionKey() && array_key_exists('brq_transactions', $this->_postArray)) {
            $this->_order->setTransactionKey($this->_postArray['brq_transactions']);
        }

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/cancel_on_failed', $this->getStoreId())) {
            $this->_order->cancel()
                         ->save();
            if ($description) {
                $this->_order->addStatusHistoryComment(Mage::helper('buckaroo3extended')->__($description))
                             ->save();
            }
        } else {
            $this->_order->setState($newStates[0], $newStates[1], Mage::helper('buckaroo3extended')->__($description))
                         ->save();
        }

        return true;
    }

    /**
     * Processes an order for which an incorrect amount has been paid (can only happen with Overschrijving)
     *
     * @return boolean
     */
    protected function _processIncorrectPayment($newStates)
    {
        //determine whether too much or not enough has been paid and determine the status history copmment accordingly
        $amount = round($this->_order->getBaseGrandTotal()*100, 0);
        $currency = $this->_order->getBaseCurrencyCode();

        if ($amount > $this->_postArray['brq_amount']) {
            $setState = $newStates[0];
            $setStatus = $newStates[1];
            $description = Mage::helper('buckaroo3extended')->__('te weinig betaald: ')
                            . round(($this->_postArray['brq_amount'] / 100), 2)
                            . ' '
                            . $currency
                            . Mage::helper('buckaroo3extended')->__(' is overgemaakt. Order bedrag was: ')
                           . round($this->_order->getGrandTotal(), 2)
                            . ' '
                            . $currency;
        } elseif ($amount < $this->_postArray['bpe_amount']) {
            $setState = $newStates[0];
            $setStatus = $newStates[1];
            $description = Mage::helper('buckaroo3extended')->__('te veel betaald: ')
                            . round(($this->_postArray['brq_amount'] / 100), 2)
                            . ' '
                            . $currency
                            . Mage::helper('buckaroo3extended')->__(' is overgemaakt. Order bedrag was: ')
                            . round($this->_order->getGrandTotal(), 2)
                            . ' '
                            . $currency;
        } else {
            //the correct amount was actually paid, so return false
            return false;
        }

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_transfer/on_hold_email')) {
            $this->_sendOverschrijvingOnHoldEmail();
        }

        //hold the order
        $this->_order->hold()
                     ->save();
        $this->_order->setState($setState, $setStatus, Mage::helper('buckaroo3extended')->__($description))
                     ->save();

        return true;
    }

    /**
     * processes an order awaiting payment. Sets its new state and status.
     *
     * @param array $newStates
     * @param string $description
     *
     * @return boolean
     */
    protected function _processPendingPayment($newStates, $description = false)
    {
        $description = Mage::helper('buckaroo3extended')->__($description);
        $description .= " (#{$this->_postArray['brq_statuscode']})";

        //sets the transaction key if its defined ($trx)
        //will retrieve it from the response array, if response actually is an array
        if (!$this->_order->getTransactionKey() && array_key_exists('brq_transactions', $this->_postArray)) {
            $this->_order->setTransactionKey($this->_postArray['brq_transactions']);
        }

        $this->_order->setState($newStates[0], $newStates[1], $description)
                     ->save();

        return true;
    }

    public function getNewStates($code)
    {
        return $this->_getNewStates($code);
    }

    public function processPendingPayment($newStates, $description = false) 
    {
        return $this->_processPendingPayment($newStates, $description);
    }

    public function processSuccess($newStates, $description = false) 
    {
        return $this->_processPendingPayment($newStates, $description);
    }

    public function processFailed($newStates, $description = false) 
    {
        return $this->_processPendingPayment($newStates, $description);
    }

    public function processIncorrectPayment($newStates) 
    {
        return $this->_processPendingPayment($newStates);
    }

    /**
     * Determines the signature using array sorting and the SHA1 hash algorithm
     *
     * @param array $origArray
     *
     * @return string $signature
     */
    protected function _calculateSignature()
    {
        if (isset($this->_postArray['isOldPost']) && $this->_postArray['isOldPost'])
        {
            return $this->_calculateOldSignature();
        }

        $origArray = $this->_postArray;
        unset($origArray['brq_signature']);

        //sort the array
        $sortableArray = $this->buckarooSort($origArray);

        //turn into string and add the secret key to the end
        $signatureString = '';
        foreach($sortableArray as $key => $value) {
            $value = urldecode($value);
            $signatureString .= $key . '=' . $value;
        }

        $signatureString .= Mage::getStoreConfig('buckaroo/buckaroo3extended/digital_signature', $this->getStoreId());

        $this->_debugEmail .= "\nSignaturestring: {$signatureString}\n";

        //return the SHA1 encoded string for comparison
        $signature = SHA1($signatureString);

        $this->_debugEmail .= "\nSignature: {$signature}\n";

        return $signature;
    }

    protected function _calculateOldSignature()
    {
        $signature2 = md5(
            $this->_postArray['oldPost']["bpe_trx"]
            . $this->_postArray['oldPost']["bpe_timestamp"]
            . Mage::getStoreConfig('buckaroo/buckaroo3extended/key', $this->getStoreId())
            . $this->_postArray['oldPost']["bpe_invoice"]
            . $this->_postArray['oldPost']["bpe_reference"]
            . $this->_postArray['oldPost']["bpe_currency"]
            . $this->_postArray['oldPost']["bpe_amount"]
            . $this->_postArray['oldPost']["bpe_result"]
            . $this->_postArray['oldPost']["bpe_mode"]
            . Mage::getStoreConfig('buckaroo/buckaroo3extended/digital_signature', $this->getStoreId())
        );

        return $signature2;
    }
}
