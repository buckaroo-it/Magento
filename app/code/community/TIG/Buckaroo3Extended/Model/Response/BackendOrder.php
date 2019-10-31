<?php
class TIG_Buckaroo3Extended_Model_Response_BackendOrder extends TIG_Buckaroo3Extended_Model_Response_Abstract
{
    protected function _success($status = self::BUCKAROO_SUCCESS)
    {
        $this->_debugEmail .= "The request was successful \n";
        if(!$this->_order->getEmailSent())
        {
            $this->_order->sendNewOrderEmail();
        }

        Mage::getSingleton('core/session')->addSuccess(
            Mage::helper('buckaroo3extended')->__('Your order has been placed succesfully.')
        );
        $this->sendDebugEmail();
    }

    protected function _failed($message = '')
    {
        $this->_debugEmail .= 'The request failed \n';
        $this->restoreQuote();

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/cancel_on_failed', $this->_order->getStoreId())) {
            $this->_order->cancel()->save();
        }

        $this->sendDebugEmail();
        Mage::throwException('An error occurred while processing the payment request, check the Buckaroo debug e-mail for details.');
    }

    protected function _error($message = '')
    {
        $this->_debugEmail .= "The request generated an error \n";

        $newInvoiceIsCanceled = false;

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoices */
        $invoices = $this->_order->getInvoiceCollection();

        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        foreach ($invoices->getItems() as $invoice) {
            if ($invoice->isObjectNew() && $invoice->getRequestedCaptureCase() == 'online') {
                $newInvoiceIsCanceled = true;
                $invoice->cancel()->save()->delete();
            }
        }

        if (!$newInvoiceIsCanceled) {
            $this->_order->cancel()->save();
        }

        $this->_debugEmail .= "I have cancelled the order! \n";

        $this->sendDebugEmail();
        Mage::throwException('An error occurred while processing the payment request, check the Buckaroo debug e-mail for details.');
    }

    protected function _neutral()
    {
        $this->_debugEmail .= "The request was neutral \n";

        Mage::getSingleton('core/session')->addSuccess(
            Mage::helper('buckaroo3extended')->__(
                'Your order has been placed succesfully. You will receive an e-mail containing further payment instructions shortly.'
            )
        );

        $this->sendDebugEmail();
    }

    protected function _pendingPayment()
    {
        $this->_success();
    }

    protected function _incorrectPayment($message = '')
    {
        $this->_error($message);
    }

    protected function _verifyError()
    {
        $this->_debugEmail .= "The response could not be verified \n";
        Mage::getSingleton('core/session')->addNotice(
            Mage::helper('buckaroo3extended')->__('We are currently unable to retrieve the status of your transaction. If you do not receive an e-mail regarding your order within 30 minutes, please contact the shop owner.')
        );

        $this->_order->cancel()->save();
        $this->_debugEmail .= "I have cancelled the order! \n";

        $this->sendDebugEmail();
        Mage::throwException('An error occurred while processing the request');
    }
}
