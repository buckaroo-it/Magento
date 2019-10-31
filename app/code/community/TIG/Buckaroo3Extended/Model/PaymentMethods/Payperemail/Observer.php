<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Payperemail_Observer extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code = 'buckaroo3extended_payperemail';
    protected $_method = 'payperemail';

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $vars = $request->getVars();

        $array = array(
            $this->_method     => array(
                'action'    => 'PaymentInvitation',
                'version'   => 1,
            ),
        );

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

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function buckaroo3extended_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request            = $observer->getRequest();
        $this->_billingInfo = $request->getBillingInfo();
        $this->_order       = $request->getOrder();

        $vars = $request->getVars();

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_' . $this->_method . '/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
            $this->_addCustomerVariables($vars);
            $this->_addCreditManagement($vars);
            $this->_addAdditionalCreditManagementVariables($vars);
        }

        if (!Mage::helper('buckaroo3extended')->isAdmin()) {
            $additionalFields = Mage::getSingleton('checkout/session')->getData('additionalFields');
        } else {
            $additionalFields = Mage::getSingleton('core/session')->getData('additionalFields');
        }

        if (is_array($additionalFields)
            && array_key_exists('gender', $additionalFields)
            && array_key_exists('mail', $additionalFields)
            && array_key_exists('firstname', $additionalFields)
            && array_key_exists('lastname', $additionalFields)
        ) {
            $array = array(
                'customergender'        => $additionalFields['gender'],
                'CustomerEmail'         => $additionalFields['mail'],
                'CustomerFirstName'     => $additionalFields['firstname'],
                'CustomerLastName'      => $additionalFields['lastname'],
            );
        } else {
            $array = array();
        }

        $array['MerchantSendsEmail'] = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_payperemail/send_mail', $this->_order->getStoreId()
        ) ? 'false' : 'true';
        $array['PaymentMethodsAllowed'] = $this->_getPaymentMethodsAllowed();

        if (array_key_exists('customVars', $vars) && array_key_exists($this->_method, $vars['customVars']) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $array);
        } else {
            $vars['customVars'][$this->_method] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    /**
     * While PayPerEmail is the payment method for this transaction, the transaction is actually completed using another
     * payment method. This observer stores that payment method in the database. This is currently only used for online
     * refunds.
     *
     * This method also prevents secondary transactions from cancelling the order prematurely.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_push_custom_processing(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        /**
         * @var TIG_Buckaroo3Extended_Model_Response_Push $push
         * @var Mage_Sales_Model_Order $order
         * @var array $postArray
         * @var array $response
         */
        $push      = $observer->getPush();
        $order     = $observer->getOrder();
        $postArray = $push->getPostArray();
        $response  = $observer->getResponse();

        /**
         * If this push is for a secondary transaction for a PPE order, we may only process 'successful' updates.
         */
        if ($postArray['brq_transaction_method'] != $this->_code
            && $response['status'] != self::BUCKAROO_SUCCESS
        ){
            /**
             * This flag will prevent further processing of the push.
             */
            $push->setCustomResponseProcessing(true);
            return $this;
        }

        /**
         * If this push is made by a secondary transaction and if the push indicates a successful payment, update the
         * order's payment method to reflect the chosen payment method.
         */
        if (isset($postArray['brq_payment_method'])
            && !$order->getPaymentMethodUsedForTransaction()
            && $response['status'] == self::BUCKAROO_SUCCESS
        ) {
            $order->setPaymentMethodUsedForTransaction($postArray['brq_payment_method']);
        } elseif (isset($postArray['brq_transaction_method'])
            && !$order->getPaymentMethodUsedForTransaction()
            && $response['status'] == self::BUCKAROO_SUCCESS
        ) {
            $order->setPaymentMethodUsedForTransaction($postArray['brq_transaction_method']);
        }

        $order->save();

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
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

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
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
     * @return $this
     */
    public function buckaroo3extended_refund_request_addservices(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $refundRequest = $observer->getRequest();

        $vars = $refundRequest->getVars();

        $array = array(
            'action'    => 'Refund',
            'version'   => 1,
        );

        if (array_key_exists('services', $vars) && is_array($vars['services'][$this->_method])) {
            $vars['services'][$this->_method] = array_merge($vars['services'][$this->_method], $array);
        } else {
            $vars['services'][$this->_method] = $array;
        }

        $refundRequest->setVars($vars);

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function buckaroo3extended_refund_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        return $this;
    }

    /**
     * Alternative return processing for PPE orders. In the case of a PPE order, customers may choose to pay using
     * another payment method, such as iDEAL. This will create a second transaction in payment plaza, linked to the PPE
     * transaction. If the customer cancels this second transaction, the shop will be updated with a cancel request for
     * the entire order. The customer may however choose another payment method and pay using that. In this case the
     * third, successful transaction will be ignored by Magento as the order has already been cancelled by the second,
     * unsuccessful transaction. To prevent this, cancellation requests may only be processed for PPE orders if they are
     * sent by the initial PPE transaction and not by a secondary transaction.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @event buckaroo3extended_return_custom_processing
     *
     * @observer buckaroo3extended_paymentmethod_observer_payperemail_return
     */
    public function buckaroo3extended_return_custom_processing(Varien_Event_Observer $observer)
    {
        /**
         * Make sure the order was placed using PPE.
         *
         * @var Mage_Sales_Model_Order $order
         */
        $order = $observer->getOrder();
        if ($order->getPayment()->getMethod() !== $this->_code) {
            return $this;
        }

        /**
         * Get the source data, including the 'return' model.
         *
         * @var array $pushData
         * @var TIG_Buckaroo3Extended_Model_Response_Return $return
         */
        $pushData = $observer->getPostArray();
        $return = $observer->getReturn();

        /**
         * Check the status code sent by Buckaroo.
         */
        $statusCodes = $return->responseCodes;
        $statusCode = $pushData['brq_statuscode'];
        if (!array_key_exists($statusCode, $statusCodes)) {
            return $this;
        }

        /**
         * Parse the status code.
         */
        $status = $statusCodes[$statusCode];

        /**
         * If the payment method of the current transaction is not PPE, we may only process 'successful' status codes.
         */
        if ($pushData['brq_payment_method'] !== $this->_method
            && $status['status'] != $return::BUCKAROO_SUCCESS
        ) {
            /**
             * Add a note to the order to indicate this request was not processed.
             */
            $order->addStatusHistoryComment(
                Mage::helper('buckaroo3extended')->__(
                    'The customer attempted to pay this order using %s, but cancelled the payment.',
                    $pushData['brq_payment_method']
                )
            )->save();
            $return->setCustomResponseProcessing(true);
        }

        return $this;
    }
}
