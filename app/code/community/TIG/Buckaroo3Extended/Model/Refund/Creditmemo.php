<?php
class TIG_Buckaroo3Extended_Model_Refund_Creditmemo extends TIG_Buckaroo3Extended_Model_Refund_Response_Push
{
    protected $_request;

    /**
     * This is called when a refund is made in Buckaroo Payment Plaza.
     * This Function will result in a creditmemo being created for the order in question.
     */
    public function processBuckarooRefundPush()
    {
        //check if the push is valid and if the order can be updated
        list($valid, $canProcess) = $this->_canProcessRefundPush();

        $this->_debugEmail .= "Is the PUSH valid? " . $valid . "\nCan the creditmemo be created? " . $canProcess . "\n";

        if (!$valid || !$canProcess) {
            return false;
        }

        $success = $this->_createCreditmemo();

        if ($success === false) { //if $success === true, the observer will update the status instead
            $this->_updateRefundedOrderStatus($success);
        }

        return true;
    }

    protected function _createCreditmemo()
    {
        $data = $this->_getCreditmemoData();

        try {
            $creditmemo = $this->_initCreditmemo($data);
            if ($creditmemo) {
                if (($creditmemo->getGrandTotal() <= 0) && (!$creditmemo->getAllowZeroGrandTotal())) {
                    Mage::throwException(
                        Mage::helper('buckaroo3extended')->__('Credit memo\'s total must be positive.')
                    );
                }

                $comment = '';
                if (!empty($data['comment_text'])) {
                    $creditmemo->addComment(
                        $data['comment_text'],
                        isset($data['comment_customer_notify']),
                        isset($data['is_visible_on_front'])
                    );
                    if (isset($data['comment_customer_notify'])) {
                        $comment = $data['comment_text'];
                    }
                }

                if (isset($data['do_refund'])) {
                    $creditmemo->setRefundRequested(true);
                }

                if (isset($data['do_offline'])) {
                    $creditmemo->setOfflineRequested((bool)(int)$data['do_offline']);
                }

                $creditmemo->setTransactionKey($this->_postArray['brq_transactions']);

                $creditmemo->register();
                if (!empty($data['send_email'])) {
                    $creditmemo->setEmailSent(true);
                }

                $creditmemo->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                $this->_saveCreditmemo($creditmemo);
                $creditmemo->sendEmail(!empty($data['send_email']), $comment);

                Mage::getSingleton('adminhtml/session')->getCommentText(true);

                return true;
            } else {
                return false;
            }
        } catch (Mage_Core_Exception $e) {
            $this->logException($e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return false;
        }

        return true;
    }

    protected function _initCreditmemo($data, $update = false)
    {
        $request = $this->getRequest();
        $request->setParam('creditmemo', $data);


        $creditmemo = false;

        $order  = $this->_order;

        /** @var Mage_Sales_Model_Service_Order $service */
        $service = Mage::getModel('sales/service_order', $order);

        $savedData = $this->_getItemData($data);

        $qtys = array();
        foreach ($savedData as $orderItemId =>$itemData) {
            if (isset($itemData['qty'])) {
                $qtys[$orderItemId] = $itemData['qty'];
            }
        }

        $data['qtys'] = $qtys;
        $creditmemo = $service->prepareCreditmemo($data);

        /**
         * Process back to stock flags
         */
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            $creditmemoItem->setBackToStock(false);
        }

        $args = array('creditmemo' => $creditmemo, 'request' => $request);
        Mage::dispatchEvent('adminhtml_sales_order_creditmemo_register_before', $args);

        Mage::register('current_creditmemo', $creditmemo);
        return $creditmemo;
    }

    /**
     * Save creditmemo and related order, invoice in one transaction
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     */
    protected function _saveCreditmemo($creditmemo)
    {
        $transactionSave = Mage::getModel('core/resource_transaction')
                               ->addObject($creditmemo)
                               ->addObject($creditmemo->getOrder());
        if ($creditmemo->getInvoice()) {
            $transactionSave->addObject($creditmemo->getInvoice());
        }

        $transactionSave->save();

        return $this;
    }

    /**
     * Get requested items qtys and return to stock flags
     */
    protected function _getItemData($data = false)
    {
        if (isset($data['items'])) {
            $qtys = $data['items'];
        } else {
            $qtys = array();
        }

        return $qtys;
    }

    /**
     * Most of the code used to create a creditmemo is copied and modified from the default magento code.
     * However, that code expects an array with values. This method creates that array.
     *
     * @return array $data
     */
    protected function _getCreditmemoData()
    {
        $totalAmount = $this->_calculateTotalAmount();

        $data = array(
            'do_offline'      => '0',
            'do_refund'       => '0',
            'comment_text'    => '',
        );

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/creditmemo_mail', $this->_order->getStoreId())) {
            $data['send_email'] = true;
        }

        $totalToRefund = $totalAmount + $this->_order->getBaseTotalRefunded();
        if ($totalToRefund == $this->_order->getBaseGrandTotal()) {
            //calculates the total adjustments made by previous creditmemos
            $creditmemos = $this->_order->getCreditmemosCollection();
            $totalAdjustment = 0;
            foreach($creditmemos as $creditmemo) {
                $adjustment = $creditmemo->getBaseAdjustmentPositive() - $creditmemo->getBaseAdjustmentNegative();
                $totalAdjustment += $adjustment;
            }

            //if the amount to be refunded + the amount that has already been refunded equals the order's base grandtotal
            //all products from that order will be refunded as well
            $data['shipping_amount']     = $this->_order->getBaseShippingAmount() - $this->_order->getBaseShippingRefunded();
            $data['adjustment_negative'] = $totalAdjustment;

            $remainder = $this->_calculateRemainder();
            //if the totalAmount equals te grandTotal the paymentFee is inside the credit Amount and should not be
            //an positive adjustment.
            if($totalAmount == $this->_order->getBaseGrandTotal()){
                $remainder = 0;
            }

            $data['adjustment_positive'] = $remainder;
        } else {
            //If this is the first adjustment refund on the order, than the fee must be count off.
            $creditAmount = $this->_setPaymentfeeRefund();
            //if the above is not the case; no products will be refunded and this refund will be considered an
            //adjustment refund
            $data['shipping_amount']     = '0';
            $data['adjustment_negative'] = '0';
            $data['adjustment_positive'] = $creditAmount;
        }

        $items = $this->_getCreditmemoDataItems();

        $data['items'] = $items;

        return $data;
    }

    protected function _setPaymentfeeRefund()
    {
        $totalAmount = $this->_calculateTotalAmount();
        if(0 == $this->_order->getBaseTotalRefunded()){
            $totalAmount = $totalAmount - ($this->_order->getBaseBuckarooFee() + $this->_order->getBaseBuckarooFeeTaxInvoiced());
        }

        return $totalAmount;
    }

    protected function _calculateTotalAmount()
    {
        $amountPushed = $this->_postArray['brq_amount_credit'];

        $baseCurrency  = $this->_order->getBaseCurrency()->getCode();
        $currency      = $this->_postArray['brq_currency'];

        if ($baseCurrency == $currency) {
            return $amountPushed;
        } else {
            $amount = round($amountPushed * $this->_order->getBaseToOrderRate(), 2);
            return $amount;
        }
    }

    /**
     * Calculates the amount left over after discounts, shipping, taxes, adjustments and the subtotal have been
     * taken into account. This remainder is probably caused by some module such as a paymentfee.
     *
     * This method will return 0 in most cases.
     */
    protected function _calculateRemainder()
    {
        $baseTotalToBeRefunded = (
                                   $this->_order->getBaseShippingAmount()
                                   - $this->_order->getBaseShippingRefunded()
                               ) + (
                                   $this->_order->getBaseSubtotal()
                                   - $this->_order->getBaseSubtotalRefunded()
                               ) + (
                                   $this->_order->getBaseAdjustmentNegative()
                                   - $this->_order->getBaseAdjustmentPositive()
                               ) + (
                                   $this->_order->getBaseTaxAmount()
                                   - $this->_order->getBaseTaxRefunded()
                               ) + (
                                   $this->_order->getBaseDiscountAmount()
                                   - $this->_order->getBaseDiscountRefunded()
                               );

        $remainderToBeRefunded = $this->_order->getBaseGrandTotal()
                               - $baseTotalToBeRefunded
                               - $this->_order->getBaseTotalRefunded();


        return $remainderToBeRefunded;
    }
    /**
     * Determines which items need to be refunded. If the amount to be refunded equals the order base grandtotal
     * then all items are refunded, otherwise none are
     */
    protected function _getCreditmemoDataItems()
    {
        $items = array();
        foreach ($this->_order->getAllItems() as $orderItem)
        {
            if (!array_key_exists($orderItem->getId(), $items)) {
                $creditAmount = 0;
                if (isset($this->_postArray['brq_amount_credit'])) {
                    $creditAmount = $this->_postArray['brq_amount_credit'];
                }

                $totalAmountInCredit = $creditAmount + $this->_order->getBaseTotalRefunded();

                if ($totalAmountInCredit == $this->_order->getBaseGrandTotal()) {
                    $qty = $orderItem->getQtyInvoiced() - $orderItem->getQtyRefunded();
                } else {
                    $qty = 0;
                }

                $items[$orderItem->getId()] = array(
                    'qty' => $qty,
                );
            }
        }

        return $items;
    }

    /**
     * Checks if the post received is valid by checking its signature field.
     * This field is unique for every payment and every store.
     * Also calls a method that checks if the order is able to have a creditmemo
     *
     * @return array $return
     */
    protected function _canProcessRefundPush()
    {
        $correctSignature = false;
        $canProcess = false;
        $signature = $this->_calculateSignature();
        if ($signature === $this->_postArray['brq_signature']) {
            $correctSignature = true;
        }

        //check if the order can receive a new creditmemo
        if ($correctSignature === true) {
            $canProcess = $this->_canProcessCreditmemo();
        }

        $return = array(
            (bool) $correctSignature,
            (bool) $canProcess,
        );
        return $return;
    }

    protected function _canProcessCreditmemo()
    {
        if (!$this->_order->canCreditmemo()) {
            return false;
        }

        if (!Mage::getStoreConfig('buckaroo/buckaroo3extended_refund/allow_push')) {
            return false;
        }

        return true;
    }
}
