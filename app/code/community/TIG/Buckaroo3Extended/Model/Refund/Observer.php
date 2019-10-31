<?php
class TIG_Buckaroo3Extended_Model_Refund_Observer extends Mage_Core_Model_Abstract
{
    public function sales_order_payment_refund(Varien_Event_Observer $observer)
    {
        $payment = $observer->getPayment();
        $creditmemo = $observer->getCreditmemo();

        if (!$creditmemo->getTransactionKey()) {
            $creditmemo->setTransactionKey($payment->getTransactionKey());
            $payment->setTransactionKey(null)->save(); //the transaction key needs to be reset after every refund
        }

        $order = $creditmemo->getOrder();
        $this->_updateRefundedOrderStatus($creditmemo, $order, true);

        return $this;
    }

    protected function _updateRefundedOrderStatus($creditmemo, $order, $success)
    {
        if (!$creditmemo->getTransactionKey()) {
            return false;
        }

        $successString = $success ? 'success' : 'failed';
        $state = $order->getState();

        if ($success) {
            $comment = 'Buckaroo refund request was successfully processed.';
        } else {
            $comment = 'Unfortunately the Buckaroo refund request could not be processed succesfully.';
        }

        if ($order->getBaseGrandTotal() != $order->getBaseTotalRefunded()) {
            $configField = "buckaroo/buckaroo3extended_refund/order_status_partial_{$state}_{$successString}";
            $status = Mage::getStoreConfig($configField, $order->getStoreId());
        } else {
            $status = null;
        }

        if (!empty($status) && $order->getStatus() != $status) {
            $order->setStatus($status)->save();
            $order->addStatusHistoryComment($comment, $status)
                 ->save();
        } else {
            $order->addStatusHistoryComment($comment)
                 ->save();
        }
    }
}
