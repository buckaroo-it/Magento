<?php
class TIG_Buckaroo3Extended_Model_Response_Push extends TIG_Buckaroo3Extended_Model_Response_Abstract
{
    const PAYMENTCODE = 'buckaroo3extended';
    const BUCK_PUSH_ACCEPT_AUTHORIZE_TYPE = 'I013';

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order = '';

    protected $_postArray = '';
    protected $_debugEmail = '';
    protected $_method = '';

    protected $creditCardMethods = array(
        'mastercard',
        'visa'
    );

    public function setCurrentOrder($order)
    {
        $this->_order = $order;
    }

    public function getCurrentOrder()
    {
        return $this->_order;
    }

    public function setPostArray($array)
    {
        $this->_postArray = $array;
    }

    public function getPostArray()
    {
        return $this->_postArray;
    }

    public function setMethod($method)
    {
        $this->_method = $method;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function setDebugEmail($debugEmail)
    {
        $this->_debugEmail = $debugEmail;
    }

    public function getDebugEmail()
    {
        return $this->_debugEmail;
    }

    public function __construct($data = array())
    {
        if (!empty($data['order'])) {
            $this->setCurrentOrder($data['order']);
        }

        if (!empty($data['postArray'])) {
            $this->setPostArray($data['postArray']);
        }

        if (!empty($data['debugEmail'])) {
            $this->setDebugEmail($data['debugEmail']);
        }

        if (!empty($data['method'])) {
            $this->setMethod($data['method']);
        }
    }

    /**
     * Processes 'pushes' receives from Buckaroo with the purpose of updating an order or payment.
     *
     * @return bool
     */
    public function processPush()
    {
        $response = $this->_parsePostResponse($this->_postArray['brq_statuscode']);

        //check if the push is valid and if the order can be updated
        list($canProcess, $canUpdate) = $this->_canProcessPush(false, $response);

        $this->_debugEmail .= "Can the order be processed? " . $canProcess . "\n"."Can the order be updated? " . $canUpdate . "\n";

        if (!$canProcess) {
            return false;
        } elseif ($canProcess && !$canUpdate) {
            //if the order cant be updated, try to add a notification to the status history instead
            $response = $this->_parsePostResponse($this->_postArray['brq_statuscode']);
            $this->_addNote($response['message'], $this->_method);
            return false;
        }

        $paymentMethod = $this->_order->getPayment()->getMethod();

        if ($paymentMethod == 'buckaroo3extended_giftcards') {
            Mage::dispatchEvent('buckaroo3extended_push_custom_processing', array('push' => $this, 'order' => $this->getCurrentOrder()));

            if ($this->getCustomResponseProcessing()) {
                return true;
            }
        }

        $newStates = $this->getNewStates($response['status']);

        $this->_debugEmail .= "Response received: " . var_export($response, true) . "\n\n";
        $this->_debugEmail .= "Current state: " . $this->_order->getState() . "\nCurrent status: " . $this->_order->getStatus() . "\n";
        $this->_debugEmail .= "New state: " . $newStates[0] . "\nNew status: " . $newStates[1] . "\n\n";

        Mage::dispatchEvent('buckaroo3extended_push_custom_processing', array('push' => $this, 'order' => $this->getCurrentOrder(), 'response' => $response));

        if ($this->getCustomResponseProcessing()) {
            return true;
        }

        switch ($response['status'])
        {
            case self::BUCKAROO_ERROR:
            case self::BUCKAROO_FAILED:               $updatedFailed = $this->_processFailed($newStates, $response['message']);
                break;
            case self::BUCKAROO_SUCCESS:           $updatedSuccess = $this->_processSuccess($newStates, $response['message']);
                break;
            case self::BUCKAROO_NEUTRAL:           $this->_addNote($response['message']);
                break;
            case self::BUCKAROO_PENDING_PAYMENT:   $updatedPendingPayment = $this->_processPendingPayment($newStates, $response['message']);
                break;
            case self::BUCKAROO_INCORRECT_PAYMENT: $updatedIncorrectPayment = $this->_processIncorrectPayment($newStates);
                break;
        }

        //revert the original status in order complete the whole process like it should.
        if (isset($originalResponseStatus)) {
            if($response['status'] == self::BUCKAROO_NEUTRAL){
                $response['status'] = $originalResponseStatus;
            }
        }

        Mage::dispatchEvent(
            'buckaroo3extended_push_custom_processing_after',
            array(
                'push' => $this,
                'order' => $this->getCurrentOrder(),
                'response' => $response
            )
        );

        if (isset($updatedFailed) && $updatedFailed) {
            $this->_debugEmail .= "Succesfully updated 'failed' state and status \n";
        } elseif (isset($updatedSuccess) && $updatedSuccess) {
            $this->_debugEmail .= "Succesfully updated 'success' state and status \n";
        } elseif (isset($updatedPendingPayment) && $updatedPendingPayment) {
            $this->_debugEmail .= "Succesfully updated pending payment \n";
        } elseif (isset($updatedIncorrectPayment) && $updatedIncorrectPayment) {
            $this->_debugEmail .= "Succesfully updated incorrect payment \n";
        } else {
            $this->_debugEmail .= "Order was not updated \n";
        }

        return true;
    }

    /**
     * @return bool
     * @throws Zend_Currency_Exception
     */
    public function processPartialTransferMessage()
    {
        $response = $this->_parsePostResponse($this->_postArray['brq_statuscode']);

        $description = Mage::helper('buckaroo3extended')->__($response['message']);
        $description .= " (#{$this->_postArray['brq_statuscode']})<br>";

        if ($this->_postArray['brq_currency'] == $this->_order->getBaseCurrencyCode()) {
            $currencyCode = $this->_order->getBaseCurrencyCode();
        } else {
            $currencyCode = $this->_order->getOrderCurrencyCode();
        }

        $brqAmount = Mage::app()->getLocale()->currency($currencyCode)->toCurrency($this->_postArray['brq_amount']);

        $description .= 'Partial amount of ' . $brqAmount . ' has been paid';

        $this->_order->addStatusHistoryComment($description)->save();

        return true;
    }


    /**
     * Checks if the post received is valid by checking its signature field.
     * This field is unique for every payment and every store.
     * Also calls method that checks if an order is able to be updated further.
     * Canceled, completed, holded etc. orders are not able to be updated
     *
     * @param bool $isReturn
     * @param array $response
     * @return array
     */
    protected function _canProcessPush($isReturn = false, $response = array())
    {
        $correctSignature = false;
        $canUpdate        = false;
        $signature        = $this->_calculateSignature();
        if ($signature === $this->_postArray['brq_signature']) {
            $correctSignature = true;
        }

        //check if the order can receive further status updates
        if ($correctSignature === true) {
            $canUpdate = $this->_canUpdate($response);
        }

        $return = array(
            (bool) $correctSignature,
            (bool) $canUpdate,
        );
        return $return;
    }

    /**
     * Checks if the order can be updated by checking if its state and status is not
     * complete, closed, cancelled or holded and the order can be invoiced
     *
     * @param array $response
     *
     * @return boolean
     */
    protected function _canUpdate($response = array())
    {

        // Get successful state and status
        $completedStateAndStatus  = array('complete', 'complete');
        $cancelledStateAndStatus  = array('canceled', 'canceled');
        $holdedStateAndStatus     = array('holded', 'holded');
        $closedStateAndStatus     = array ('closed','closed');

        $currentStateAndStatus    = array($this->_order->getState(), $this->_order->getStatus());

        //prevent completed orders from recieving further updates
        if($completedStateAndStatus != $currentStateAndStatus
            && $cancelledStateAndStatus != $currentStateAndStatus
            && $holdedStateAndStatus    != $currentStateAndStatus
            && $closedStateAndStatus    != $currentStateAndStatus
            && $this->_order->canInvoice()
        ){
            return true;
        }

        //when payperemail is used and the order has the status other then success, and current pushed status is success; send email to shopowner
        if(!empty($response)
            && $response['status']                      == self::BUCKAROO_SUCCESS
            && $this->_order->getPayment()->getMethod() == 'buckaroo3extended_payperemail'
            && (
                $currentStateAndStatus    == $completedStateAndStatus
                || $currentStateAndStatus == $cancelledStateAndStatus
                || $currentStateAndStatus == $holdedStateAndStatus
                || $currentStateAndStatus == $closedStateAndStatus
               )
        ){
            $this->_sendDoubleTransactionEmail();
        }

        $this->_debugEmail .= "order already has succes, complete, closed, or holded state or can't be invoiced \n\n";


        return false;
    }

    /**
     * Send the shop owner and subscribers to the debug-email an email with the message that there is a double transaction
     */
    protected function _sendDoubleTransactionEmail()
    {

        $helper             = Mage::helper('buckaroo3extended');
        $orderId            = $this->_order->getIncrementId();
        $currentOrderStatus = $this->_order->getStatus();

        $recipients         = explode(',', Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/debug_email', $this->getStoreId()));
        $recipients[]       = Mage::getStoreConfig('trans_email/ident_general/email');

        $mail               = $helper->__('Status Success received for order %s while the order currently the status %s has.', $orderId, $currentOrderStatus);

        foreach($recipients as $recipient) {
            mail(
                trim($recipient),
                'Dubbele transactie voor dezelfde order',
                $mail
            );
        }
    }

    /**
     * Uses setState to add a comment to the order status history without changing the state nor status. Purpose of the comment
     * is to inform the user of an attempted status upsate after the order has already received complete, canceled, closed or holded states
     * or the order can't be invoiced. Returns false if the config has disabled this feature.
     *
     * @param string $description
     */
    protected function _addNote($description)
    {
        $helper = Mage::helper('buckaroo3extended');

        $note = $helper->__('Buckaroo attempted to update this order after it already had ')
            . '<b>'
            . strtoupper($this->_order->getState())
            . '</b>'
            . $helper->__(' state, by sending the following: ')
            . '<br/>--------------------------------------------------------------------------------------------------------------------------------<br/>'
            . $description
            . ' ('
            . $this->_postArray['brq_statuscode']
            . ')';
        $this->_order->addStatusHistoryComment($note)
                     ->save();
    }

    /**
     * @param $description
     */
    public function addNote($description)
    {
        $this->_addNote($description);
    }

    /**
     * Process a succesful order. Sets its new state and status, sends an order confirmation email
     * and creates an invoice if set in config.
     *
     * @param $newStates
     * @param bool $description
     * @return bool
     */
    protected function _processSuccess($newStates, $description = false)
    {
        //send new order email if it hasnt already been sent
        if(!$this->_order->getEmailSent())
        {
            $this->sendNewOrderEmail();
        }

        $this->_autoInvoice();

        $description = Mage::helper('buckaroo3extended')->__($description);
        $description .= " (#{$this->_postArray['brq_statuscode']})<br>";

        $paymentMethod = $this->_order->getPayment()->getMethodInstance();

        if ($this->_postArray['brq_currency'] == $this->_order->getBaseCurrencyCode()) {
            $currencyCode = $this->_order->getBaseCurrencyCode();
        } else {
            $currencyCode = $this->_order->getOrderCurrencyCode();
        }

        $amountToCurrency = $this->_postArray['brq_amount'];
        if (!isset($this->_postArray['brq_amount']) || $this->_postArray['brq_amount'] <= 0) {
            $amountToCurrency = $this->_order->getGrandTotal();
        }

        $brqAmount = Mage::app()->getLocale()->currency($currencyCode)->toCurrency($amountToCurrency);

        if ($paymentMethod->canPushInvoice($this->getPostArray())) {
            $description .= 'Total amount of ' . $brqAmount . ' has been paid';
        } else {
            $description .= 'Total amount of ' . $brqAmount . ' has been authorized. Please create an invoice to capture the authorized amount.';
        }

        //sets the transaction key if its defined ($trx)
        //will retrieve it from the response array, if response actually is an array
        if (!$this->_order->getTransactionKey() && array_key_exists('brq_transactions', $this->_postArray)) {
            $this->_order->setTransactionKey($this->_postArray['brq_transactions']);
            $this->_order->save();
        }

        if ($this->_order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING) {
            $this->_order->addStatusHistoryComment($description, $newStates[1])
                         ->save();

            $this->_order->setStatus($newStates[1])->save();
        } else {
            $this->_order->addStatusHistoryComment($description)
                         ->save();
        }


        return true;
    }

    /**
     * Process a failed order. Sets its new state and status and cancels the order
     * if set in config.
     *
     * @param $newStates
     * @param bool $description
     * @return bool
     */
    protected function _processFailed($newStates, $description = false)
    {
        $description = Mage::helper('buckaroo3extended')->__($description);
        $description .= " (#{$this->_postArray['brq_statuscode']})";

        //sets the transaction key if its defined ('brq_transactions')
        //will retrieve it from the response array, if response actually is an array
        if (!$this->_order->getTransactionKey() && array_key_exists('brq_transactions', $this->_postArray)) {
            $this->_order->setTransactionKey($this->_postArray['brq_transactions']);
        }

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/cancel_on_failed', $this->_order->getStoreId())
            && $this->_order->canCancel()
        ) {
            /* Do not cancel order on a failed authorize, because it will send a cancel authorize /cancel reservation
             * message to Buckaroo, this is not needed/correct.
             * brq_transaction_type = Afterpay
             * brq_datarequest = Klarna
             */
            if ($this->_postArray['brq_transaction_type'] == self::BUCK_PUSH_ACCEPT_AUTHORIZE_TYPE ||
                (isset($this->_postArray['brq_datarequest']) && $this->_postArray['brq_datarequest'] != '')
                ) {
                $payment = $this->_order->getPayment();
                $payment->setAdditionalInformation('buckaroo_failed_authorize', 1);
                $payment->save();
            }

            $this->_order->cancel();
        }

        if ($this->_order->getState() == Mage_Sales_Model_Order::STATE_CANCELED) {
            $this->_order->addStatusHistoryComment($description, $newStates[1])
                ->save();

            $this->_order->setStatus($newStates[1])->save();
        } else {
            $this->_order->addStatusHistoryComment($description)
                ->save();
        }

        return true;
    }

    /**
     * Processes an order for which an incorrect amount has been paid (can only happen with Transfer)
     *
     * @param $newStates
     * @return bool
     */
    protected function _processIncorrectPayment($newStates)
    {
        //determine whether too much or not enough has been paid and determine the status history copmment accordingly
        $amount = round($this->_order->getBaseGrandTotal()*100, 0);

        $setStatus = $newStates[1];

        if ($this->_postArray['brq_currency'] == $this->_order->getBaseCurrencyCode()) {
            $currencyCode = $this->_order->getBaseCurrencyCode();
            $orderAmount = $this->_order->getBaseGrandTotal();
        } else {
            $currencyCode = $this->_order->getOrderCurrencyCode();
            $orderAmount = $this->_order->getGrandTotal();
        }

        if ($amount > $this->_postArray['brq_amount']) {
            $description = Mage::helper('buckaroo3extended')->__(
                'Not enough paid: %s has been transfered. Order grand total was: %s.',
                Mage::app()->getLocale()->currency($currencyCode)->toCurrency($this->_postArray['brq_amount']),
                Mage::app()->getLocale()->currency($currencyCode)->toCurrency($orderAmount)
            );
        } elseif ($amount < $this->_postArray['brq_amount']) {
            $description = Mage::helper('buckaroo3extended')->__(
                'Too much paid: %s has been transfered. Order grand total was: %s.',
                Mage::app()->getLocale()->currency($currencyCode)->toCurrency($this->_postArray['brq_amount']),
                Mage::app()->getLocale()->currency($currencyCode)->toCurrency($orderAmount)
            );
        } else {
            //the correct amount was actually paid, so return false
            return false;
        }

        //hold the order
        $this->_order->hold()
                     ->save()
                     ->setStatus($setStatus)
                     ->save()
                     ->addStatusHistoryComment(Mage::helper('buckaroo3extended')->__($description), $setStatus)
                     ->save();

        return true;
    }

    /**
     * processes an order awaiting payment. Sets its new state and status.
     *
     * @param $newStates
     * @param bool $description
     * @return bool
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


        if ($this->_order->getState() == Mage_Sales_Model_Order::STATE_NEW) {
            $this->_order->addStatusHistoryComment($description, $newStates[1])
                         ->save();

            $this->_order->setStatus($newStates[1])->save();
        } else {
            $this->_order->addStatusHistoryComment($description)
                         ->save();
        }

        return true;
    }

    /**
     * @param $code
     * @return array
     */
    public function getNewStates($code)
    {
        return Mage::helper('buckaroo3extended')->getNewStates($code, $this->getOrder(), $this->_method);
    }

    /**
     * @param $newStates
     * @param bool $description
     * @return bool
     */
    public function processPendingPayment($newStates, $description = false)
    {
        return $this->_processPendingPayment($newStates, $description);
    }

    /**
     * @param $newStates
     * @param bool $description
     * @return bool
     */
    public function processSuccess($newStates, $description = false)
    {
        return $this->_processSuccess($newStates, $description);
    }

    /**
     * @param $newStates
     * @param bool $description
     * @return bool
     */
    public function processFailed($newStates, $description = false)
    {
        return $this->_processFailed($newStates, $description);
    }

    /**
     * @param $newStates
     * @return bool
     */
    public function processIncorrectPayment($newStates)
    {
        return $this->_processIncorrectPayment($newStates);
    }

    /**
     * Creates an invoice for the order if the module is configured to do so.
     *
     * @return bool
     */
    protected function _autoInvoice()
    {
        //check if the module is configured to create invoice on success
        if (!Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/auto_invoice', $this->_order->getStoreId())) {
            return false;
        }

        //Check if the method has to be authorized first before an invoice can be made
        $paymentMethod = $this->_order->getPayment()->getMethodInstance();
        if (!$paymentMethod->canPushInvoice($this->getPostArray())) {
            return false;
        }

        //returns true if invoice has been made, else false
        $invoiceSaved = $this->_saveInvoice();

        if($invoiceSaved && Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/invoice_mail', $this->_order->getStoreId())) {
            $this->sendInvoiceEmail();
        }
    }

    /**
     * Emails the invoice of the latest invoice of the current transaction
     */
    private function sendInvoiceEmail()
    {
        // We can't send an invoice if we don't know the current transaction
        if (!isset($this->_postArray['brq_transactions'])) {
            return;
        }

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
        $invoiceCollection = $this->_order->getInvoiceCollection()
            ->addFieldToFilter('transaction_id', array('eq' => $this->_postArray['brq_transactions']))
            ->setOrder('entity_id', Mage_Sales_Model_Resource_Order_Invoice_Collection::SORT_ORDER_DESC);

        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $invoiceCollection->getFirstItem();

        if ($invoice->getId() && !$invoice->getEmailSent()) {
            $comment = $this->getNewestInvoiceComment($invoice);

            $invoice->sendEmail(true, $comment)
                ->setEmailSent(true)
                ->save();
        }
    }

    /**
     * Obtain the latest invoice of the invoice
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     *
     * @return string
     */
    private function getNewestInvoiceComment($invoice)
    {
        /** @var Mage_Sales_Model_Resource_Order_Invoice_Comment_Collection $commentsCollection */
        $commentsCollection = $invoice->getCommentsCollection();

        /** @var Mage_Sales_Model_Order_Invoice_Comment $commentItem */
        $commentItem = $commentsCollection->getFirstItem();
        $commentText = '';

        if ($commentItem->getId()) {
            $commentText = $commentItem->getComment();
        }

        return $commentText;
    }

    /**
     * Saves an invoice and sets total-paid for the order
     *
     * @return bool
     */
    protected function _saveInvoice()
    {

        if ($this->_order->canInvoice() && !$this->_order->hasInvoices()) {
            $payment = $this->_order->getPayment();
            $payment->registerCaptureNotification($this->_order->getBaseGrandTotal());

            $this->_order->save();
            $this->_debugEmail .= 'Invoice created and saved. \n';

            //sets the invoice's transaction ID as the Buckaroo TRX. This is to allow the order to be refunded using Buckaroo later on.
            foreach($this->_order->getInvoiceCollection() as $invoice)
            {
                if (!isset($this->_postArray['brq_transactions'])) {
                    continue;
                }

                $invoice->setTransactionId($this->_postArray['brq_transactions'])
                        ->save();
            }

            $response = $this->_parsePostResponse($this->_postArray['brq_statuscode']);
            Mage::dispatchEvent(
                'buckaroo3extended_push_custom_save_invoice_after',
                array(
                    'push' => $this,
                    'order' => $this->getCurrentOrder(),
                    'response' => $response
                )
            );

            return true;
        }

        return false;
    }

    /**
     * Determines the signature using array sorting and the SHA1 hash algorithm
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
            $value = $this->decodePushValue($key, $value);
            $signatureString .= $key . '=' . $value;
        }

        $signatureString .= Mage::getStoreConfig('buckaroo/buckaroo3extended/digital_signature', $this->_order->getStoreId());

        $this->_debugEmail .= "\nSignaturestring: {$signatureString}\n";

        //return the SHA1 encoded string for comparison
        $signature = SHA1($signatureString);

        $this->_debugEmail .= "\nSignature: {$signature}\n";

        return $signature;
    }

    /**
     * @param $brqKey
     * @param $brqValue
     *
     * @return string
     */
    private function decodePushValue($brqKey, $brqValue)
    {
        //Only pushes need to be decoded, not responses
        if (get_class($this) == 'TIG_Buckaroo3Extended_Model_Response_Return') {
            return $brqValue;
        }

        switch ($brqKey) {
            case 'brq_SERVICE_payconiq_PayconiqAndroidUrl':
            case 'brq_SERVICE_payconiq_PayconiqIosUrl':
            case 'brq_SERVICE_payconiq_PayconiqUrl':
            case 'brq_SERVICE_payconiq_QrUrl':
            case 'brq_SERVICE_masterpass_CustomerPhoneNumber':
            case 'brq_SERVICE_masterpass_ShippingRecipientPhoneNumber':
            case 'brq_InvoiceDate':
            case 'brq_DueDate':
            case 'brq_PreviousStepDateTime':
            case 'brq_EventDateTime':
                $decodedValue = $brqValue;
                break;
            default:
                $decodedValue = urldecode($brqValue);
        }

        return $decodedValue;
    }

    /**
     * Check if the BPE 3.0 Plaza setting "Enable double encoding of redirect data" is checked
     * Timestamp will contain an "+"
     *
     * @param $postFieldTimestamp
     *
     * @return bool
     */
    protected function _checkDoubleEncoding($postFieldTimestamp)
    {
        $hasDoubleEncoding = false;
        if (strpos($postFieldTimestamp, '+') !== false) {
            $hasDoubleEncoding = true;
        }

        return $hasDoubleEncoding;
    }

    /**
     * @param $method
     *
     * @return bool
     */
    protected function _checkPaymentMethodIsCreditCard($method)
    {
        $isCreditCard = false;
        if (in_array($method, $this->creditCardMethods)) {
            $isCreditCard = true;
        }

        return $isCreditCard;
    }

    /**
     * Compatibility for BPE 2.0 pushes
     *
     * @return string
     */
    protected function _calculateOldSignature()
    {
        $signature2 = md5(
            $this->_postArray['oldPost']["bpe_trx"]
            . $this->_postArray['oldPost']["bpe_timestamp"]
            . Mage::getStoreConfig('buckaroo/buckaroo3extended/key', $this->_order->getStoreId())
            . $this->_postArray['oldPost']["bpe_invoice"]
            . $this->_postArray['oldPost']["bpe_reference"]
            . $this->_postArray['oldPost']["bpe_currency"]
            . $this->_postArray['oldPost']["bpe_amount"]
            . $this->_postArray['oldPost']["bpe_result"]
            . $this->_postArray['oldPost']["bpe_mode"]
            . Mage::getStoreConfig('buckaroo/buckaroo3extended/digital_signature', $this->_order->getStoreId())
        );

        return $signature2;
    }

    /**
     * Checks if the correct amount has been paid.
     */
    protected function _checkCorrectAmount()
    {
        return true;
    }
}
