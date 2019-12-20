<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */
class TIG_Buckaroo3Extended_Model_Observer_InvoicePay extends Mage_Core_Model_Abstract
{
    /** @var array */
    protected $_allowedMethods = array('afterpay20', 'klarna');

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function sales_order_invoice_pay(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();

        $order = $invoice->getOrder();
        $payment = $invoice->getOrder()->getPayment();

        $paymentMethodAction = $payment->getMethodInstance()->getConfigPaymentAction();

        /** The first characters are "buckaroo3extended_" which are the same for all methods.
        Therefore we don't need to validate this part. */
        $paymentMethodCode = substr($payment->getMethodInstance()->getCode(), 18);

        Mage::helper('buckaroo3extended')->devLog(__METHOD__, 1, [$paymentMethodCode, $paymentMethodAction]);

        if (in_array($paymentMethodCode, $this->_allowedMethods)
            && ($paymentMethodAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE)
        ) {
            if ($invoice && Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/invoice_mail', $order->getStoreId())) {
                $invoice->save();
                $this->sendInvoiceEmail($invoice);
            }
        }

        return $this;
    }

    /**
     * Emails the invoice of the latest invoice of the current transaction
     */
    private function sendInvoiceEmail($invoice)
    {
        if (!$invoice->getEmailSent()) {
            $comment = $this->getNewestInvoiceComment($invoice);
            $invoice->sendEmail(true, $comment);
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
}
