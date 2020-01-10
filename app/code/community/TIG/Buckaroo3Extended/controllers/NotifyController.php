<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_NotifyController extends Mage_Core_Controller_Front_Action
{

    protected $_order;
    protected $_postArray;
    protected $_debugEmail;
    protected $_paymentMethodCode;
    protected $_paymentCode;
    protected $_processPush;


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

    public function setMethod($paymentMethod)
    {
        $this->_paymentMethodCode = $paymentMethod;
    }

    public function getMethod()
    {
        return $this->_paymentMethodCode;
    }

    public function setDebugEmail($debugEmail)
    {
        $this->_debugEmail = $debugEmail;
    }

    public function getDebugEmail()
    {
        return $this->_debugEmail;
    }

    public function setPushLock($orderId)
    {
        $this->_processPush = Mage::getModel('buckaroo3extended/process')->setId('push_' . $orderId);
    }

    public function getPushLock()
    {
        return $this->_processPush;
    }

    /**
     *
     * Prevents the page from being displayed using GET
     */
    public function preDispatch()
    {
        if (!$this->validatePostData()) {
            return;
        }

        return parent::preDispatch();
    }

    /**
     * @return bool
     */
    protected function validatePostData()
    {
        $postData = $this->getRequest()->getPost();

        if (empty($postData)) {
            $this->getResponse()->clearHeaders();
            $this->getResponse()->setBody('Only Buckaroo can call this page properly.');

            return false;
        }

        return true;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     * @throws Mage_Core_Exception
     */
    protected function getAbstractModule()
    {
        Mage::register('buckaroo_push-error', true);
        $module = Mage::getModel('buckaroo3extended/abstract', $this->_debugEmail);
        $module->setDebugEmail($this->_debugEmail);

        return $module;
    }

    /**
     * @return string
     */
    protected function loadOrder()
    {
        $invoice = null;
        $orderId = $this->_postArray['brq_invoicenumber'];

        if (isset($this->_postArray['brq_relatedtransaction_refund'])) {
            /** @var Mage_Sales_Model_Order_Invoice $invoice */
            $invoice = Mage::getModel('sales/order_invoice')
                ->load($this->_postArray['brq_relatedtransaction_refund'], 'transaction_id');
        }

        if (null !== $invoice &&
            count($invoice->getData()) > 0 &&
            $invoice->getOrderIncrementId() != $this->_postArray['brq_invoicenumber']
        ) {
            $orderId = $invoice->getOrderIncrementId();
        }

        if (strpos($orderId, 'quote_') !== false) {
            $quoteId = str_replace('quote_', '', $orderId);
            $this->_order = Mage::getModel('sales/order')->load($quoteId, 'quote_id');
        } else {
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        }

        return $orderId;
    }

    /**
     *
     * Handles 'pushes' sent by Buckaroo meant to update the current status of payments/orders
     */
    public function pushAction()
    {
        if (!$this->validatePostData()) {
            return false;
        }

        $postData = $this->getRequest()->getPost();
        $this->_debugEmail = '';
        $this->_postArray = $postData;

        if (!isset($postData['brq_invoicenumber'])) {
            return false;
        }

        $this->_postArray = $postData;
        $orderId = $this->loadOrder();

        $date = Mage::getSingleton('core/date')->gmtDate();
        $this->_debugEmail .= 'Buckaroo push received at ' . $date . "\n";
        $this->_debugEmail .= 'Order ID: ' . $orderId . "\n";

        if (isset($postData['brq_test']) && $postData['brq_test'] == 'true') {
            $this->_debugEmail .= "\n/////////// TEST /////////\n";
        }

        if (!$this->_order || !$this->_order->getId()) {
            return false;
        }

        //check if push needs to skipped
        $payment = $this->_order->getPayment();
        if ($payment->getAdditionalInformation('skip_push') > 0) {
            $payment->unsAdditionalInformation('skip_push');
            $payment->save();

            $this->_debugEmail .= "\n".'We skip the first push, because this will interfere with the flow.'."\n";
            $module = $this->getAbstractModule();
            $module->sendDebugEmail();

            $this->getResponse()->setHttpResponseCode(503);
            return false;
        }

        //order exists, instantiate the lock-object for the push
        $this->setPushLock($this->_order->getId());

        if ($this->_processPush->isLocked()) {
            $this->_debugEmail .= "\n".'Currently another push is being processed, ';
            $this->_debugEmail .= 'the current push will not be processed.'."\n";
            $this->_debugEmail .= "\n".'sent from: ' . __FILE__ . '@' . __LINE__."\n";
            $module = $this->getAbstractModule();
            $module->sendDebugEmail();

            $this->getResponse()->setHttpResponseCode(503);
            return false;
        }

        $this->_processPush->lockAndBlock();
        $this->_debugEmail .= "\n".'Push is gelocked, hij kan nu verwerkt worden.'."\n";

        $this->_paymentCode = $this->_order->getPayment()->getMethod();

        $this->_debugEmail .= 'Payment code: ' . $this->_paymentCode . "\n\n";
        $this->_debugEmail .= 'POST variables received: ' . var_export($this->_postArray, true) . "\n\n";

        list($processedPush, $exceptionThrown, $module) = $this->_processPush();
        $this->_debugEmail = $module->getDebugEmail();

        if ($processedPush === false) {
            $this->_debugEmail .= 'Push was not fully processed!';
        }

        $this->_debugEmail .= "\n".' sent from: ' . __FILE__ . '@' . __LINE__;

        // Remove the lock.
        $this->_processPush->unlock();
        $this->_debugEmail .= "\n".'Push is afgerond, lock is vrij gegeven'."\n";
        //send debug email
        $module->setDebugEmail($this->_debugEmail);
        $module->sendDebugEmail();

        if ($exceptionThrown === true) {
            $errorMessage = 'Push heeft een exception ondervonden. Bekijk de log voor meer informatie.';
            Mage::throwException($errorMessage);
            $this->getResponse()->setHttpResponseCode(503);
            return false;
        }
    }

    /**
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function _processPush()
    {
        $exceptionThrown = false;

        try {
            list($module, $processedPush) = $this->_processPushAccordingToType();

            if (!is_object(($module))) {
                $module = $this->getAbstractModule();
            }
        } catch (Exception $e) {
            $this->_debugEmail .= "An Exception occurred: " . $e->getMessage() . "\n";
            $this->_debugEmail .= "\nException trace: " . $e->getTraceAsString() . "\n";

            Mage::helper('buckaroo3extended')->logException($e);
            //this will allow the script to continue unhindered
            $processedPush = false;
            $exceptionThrown = true;
            $module = $this->getAbstractModule();
        }

        return array($processedPush, $exceptionThrown, $module);
    }

    public function returnAction()
    {
        if (!$this->validatePostData()) {
            return false;
        }

        $postData = $this->getRequest()->getPost();
        if (isset($postData['brq_invoicenumber'])) {
            $orderId = $postData['brq_invoicenumber'];
        } else {
            $this->_redirect('');
            return;
        }

        if (isset($postData['brq_transaction_method'])
            && $postData['brq_transaction_method'] == 'masterpass'
            && isset($postData['brq_SERVICE_masterpass_Version'])
            && $postData['brq_SERVICE_masterpass_Version'] == 'v6'
        ) {
            /**
             * @var TIG_Buckaroo3Extended_Model_Response_MasterPass $module
             */
            try {
                $module = Mage::getModel(
                    'buckaroo3extended/response_masterPass',
                    array(
                        'postArray' => $postData,
                    )
                );

                $redirectData = $module->processReturn();
            } catch(Exception $e) {
                $helper = Mage::helper('buckaroo3extended');
                Mage::getSingleton('checkout/session')->addError(
                    $helper->__('Something went wrong while checkout out with MasterPass. Try again.')
                );
                $redirectData['path'] = 'checkout/cart';
                $redirectData['params'] = array();
            }

            if (!isset($redirectData['params'])) {
                $redirectData['params'] = array();
            }

            $this->_redirect($redirectData['path'], $redirectData['params']);

            return;
        }

        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        $this->_paymentCode = $this->_order->getPayment()->getMethod();

        $debugEmail = 'Payment code: ' . $this->_paymentCode . "\n\n";
        $debugEmail .= 'POST variables received: ' . var_export($postData, true) . "\n\n";

        /**
         * @var TIG_Buckaroo3Extended_Model_Response_Return $module
         */
        $module = Mage::getModel(
            'buckaroo3extended/response_return',
            array(
                'order'      => $this->_order,
                'postArray'  => $postData,
                'debugEmail' => $debugEmail,
                'method'     => $this->_paymentCode,
            )
        );

        $module->processReturn();
        return;
    }

    protected function _processPushAccordingToType()
    {
        if ($this->_order->getTransactionKey() == $this->_postArray['brq_transactions']
            || (isset($this->_postArray['brq_datarequest'])
                && $this->_order->getTransactionKey() == $this->_postArray['brq_datarequest']
            ) || (isset($this->_postArray['brq_relatedtransaction_partialpayment'])
                && $this->_order->getTransactionKey() == $this->_postArray['brq_relatedtransaction_partialpayment']
            )
        ) {
            list($processedPush, $module) = $this->_updateOrderWithKey();
            return array($module, $processedPush);
        }

        $this->_paymentCode = $this->_order->getPayment()->getMethod();
        $merchantKey        = Mage::getStoreConfig('buckaroo/buckaroo3extended/key', $this->_order->getStoreId());

        //fix for payperemail and klarna transactions with different transaction keys but belongs to the same order
        if ((
                ($this->_paymentCode == 'buckaroo3extended_payperemail'
                    && $this->_postArray['brq_transaction_method'] != 'payperemail'
                )
                || ($this->_paymentCode == 'buckaroo3extended_klarna'
                    && ($this->_postArray['brq_primary_service'] == 'klarna'
                        || $this->_postArray['brq_transaction_method'] == 'klarna'
                    )
                )
            )
            && $this->_order->getIncrementId() == $this->_postArray['brq_invoicenumber']
            && (
                isset($this->_postArray['brq_websitekey'])
                && $merchantKey == $this->_postArray['brq_websitekey']
            )
        ) {
            list($processedPush, $module) = $this->_updateOrderWithKey();
            return array($module, $processedPush);
        }

        $amountOrdered = $this->_order->getBaseGrandTotal();
        if ($this->_postArray['brq_currency'] == $this->_order->getOrderCurrencyCode()) {
            $amountOrdered = $this->_order->getGrandTotal();
        }

        // Save an order comment when a partial payment through transfer has been made
        if ($this->_paymentCode == 'buckaroo3extended_transfer'
            && $this->_postArray['brq_transaction_method'] == 'transfer'
            && $this->_postArray['brq_amount'] < $amountOrdered
            && $this->_order->getTransactionKey() != $this->_postArray['brq_transactions']
            && $this->_order->getIncrementId() == $this->_postArray['brq_invoicenumber']
            && (isset($this->_postArray['brq_websitekey']) && $merchantKey == $this->_postArray['brq_websitekey'])
        ) {
            list($processedPush, $module) = $this->_updateTransferPartialPaid();
            return array($module, $processedPush);
        }

        if ($this->_pushIsCreditmemo($this->_postArray)) {
            list($processedPush, $module) = $this->_updateCreditmemo();
            return array($module, $processedPush);
        }

        if (isset($this->_postArray['brq_amount_credit'])) {
            list($processedPush, $module) = $this->_newRefund();
            return array($module, $processedPush);
        }

        if (!$this->_order->getTransactionKey()) {
            list($processedPush, $module) = $this->_updateOrderWithoutKey();
            return array($module, $processedPush);
        }

        // C012, C017 and C700 are Afterpay and Klarna Capture transactions which don't need an update
        if ($this->_postArray['brq_transaction_type'] == 'C012'
            || $this->_postArray['brq_transaction_type'] == 'C017'
            || $this->_postArray['brq_transaction_type'] == 'C700'
        ) {
            list($processedPush, $module) = $this->_updateCapture();
            return array($module, $processedPush);
        }

        Mage::throwException('unable to process PUSH');
        return false;
    }

    protected function _updateTransferPartialPaid()
    {
        $this->_debugEmail .= "Order has been partial paid by Transer method. \n";

        /** @var TIG_Buckaroo3Extended_Model_Response_Push $module */
        $module = Mage::getModel(
            'buckaroo3extended/response_push',
            array(
                'order'      => $this->_order,
                'postArray'  => $this->_postArray,
                'debugEmail' => $this->_debugEmail,
                'method'     => $this->_paymentCode,
            )
        );

        $processedPush = $module->processPartialTransferMessage();

        return array($processedPush, $module);
    }

    protected function _updateOrderWithKey()
    {
        $this->_debugEmail .= "Transaction key matches the order. \n";

        $module = Mage::getModel(
            'buckaroo3extended/response_push',
            array(
                'order'      => $this->_order,
                'postArray'  => $this->_postArray,
                'debugEmail' => $this->_debugEmail,
                'method'     => $this->_paymentCode,
            )
        );

        $processedPush = $module->processPush();

        return array($processedPush, $module);
    }

    protected function _updateOrderWithoutKey()
    {
        $this->_debugEmail .= "Order does not yet have a transaction key and the PUSH does not constitute a refund. \n";

        $this->_order->setTransactionKey($this->_postArray['brq_transactions'])
            ->save();

        $this->_debugEmail .= "Transaction key saved: {$this->_postArray['brq_transactions']}";

        $module = Mage::getModel(
            'buckaroo3extended/response_push',
            array(
                'order'      => $this->_order,
                'postArray'  => $this->_postArray,
                'debugEmail' => $this->_debugEmail,
                'method'     => $this->_paymentCode,
            )
        );

        $processedPush = $module->processPush();

        return array($processedPush, $module);
    }

    /**
     * Creditmemo updates are currently not supported
     */
    protected function _updateCreditmemo()
    {
        $this->_debugEmail .= "Received PUSH to update creditmemo. "
            . "Unfortunately the module does not support creditmemo updates at this time. The PUSH is ignored.";

        $module = $this->getAbstractModule();

        return array(true, $module);
    }

    /**
     * Capture updates are currently not supported
     */
    protected function _updateCapture()
    {
        $this->_debugEmail .= "Received PUSH to update capture. "
            . "Unfortunately the module does not support capture updates at this time. The PUSH is ignored.";

        $module = $this->getAbstractModule();

        return array(true, $module);
    }

    /**
     * @return array
     */
    protected function _newRefund()
    {
        $this->_debugEmail .= "The PUSH constitutes a new refund. \n";

        if (isset($this->_postArray['ADD_refund_initiated_in_magento'])) {
            $this->_debugEmail .= "The order is already being refunded. \n";

            $module = $this->getAbstractModule();

            return array(true, $module);
        }

        $module = Mage::getModel(
            'TIG_Buckaroo3Extended_Model_Refund_Creditmemo',
            array(
                'order'      => $this->_order,
                'postArray'  => $this->_postArray,
                'debugEmail' => $this->_debugEmail,
            )
        );
        $module->setRequest($this->getRequest());

        try {
            $processedPush = $module->processBuckarooRefundPush();

            if (!empty($this->_postArray['brq_relatedtransaction_refund'])) {
                $this->_addCreditTransaction();
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return array(false, $module);
        }

        return array($processedPush, $module);
    }

    /**
     * Add credit transaction
     * Add refund transaction to transactionManager for managing partial refunds
     * with different payment methods
     */
    protected function _addCreditTransaction()
    {
        if ($this->_postArray['brq_amount_credit'] > 0 &&
            $this->_postArray['brq_statuscode'] == 190
        ) {

            $payment =  $this->_order->getPayment();
            $transactions = $payment->getAdditionalInformation('transactions');

            /** @var $transactionManager TIG_Buckaroo3Extended_Model_TransactionManager */
            $transactionManager = Mage::getModel('buckaroo3extended/transactionManager');
            $transactionManager->setTransactionArray($transactions);

            $transactionKey = $this->_postArray['brq_relatedtransaction_refund'];
            $transactionAmount = $this->_postArray['brq_amount_credit'];
            $method = $this->_postArray['brq_transaction_method'];

            $transactionManager->addCreditTransaction($transactionKey, $transactionAmount);
            $transactionManager->addHistory($transactionKey, $transactionAmount, $method, 'OK');

            $payment->setAdditionalInformation('transactions', $transactionManager->getTransactionArray());
            $payment->save();
        }
    }

    protected function _updateOrderWithoutMatchingKey()
    {
        //allow the following payment method to be performed with a different one than the initial transaction request
        if ($this->_order->getpayment()->getMethod() != $this->_postArray['brq_transaction_method']
            && (
                $this->_order->getpayment()->getMethod() == 'payperemail'
                || $this->_order->getpayment()->getMethod() == 'onlinegiro'
            )
        ) {
            return $this->_updateOrderWithKey();
        }

        Mage::throwException('Unable to match push to order or creditmemo');
    }

    protected function _pushIsCreditmemo()
    {
        foreach ($this->_order->getCreditmemosCollection() as $creditmemo) {
            if ($creditmemo->getTransactionKey() == $this->_postArray['brq_transactions']) {
                return true;
            }
        }

        return false;
    }
}
