<?php
class TIG_Buckaroo3Extended_Model_Refund_Request_Abstract extends TIG_Buckaroo3Extended_Model_Request_Abstract
{
    protected $_payment;
    protected $_invoice;
    protected $_amount;

    public function setPayment($payment)
    {
        $this->_payment = $payment;
    }

    public function getPayment()
    {
        return $this->_payment;
    }

    public function setInvoice($invoice)
    {
        $this->_invoice = $invoice;
    }

    public function getInvoice()
    {
        return $this->_invoice;
    }

    public function setAmount($amount)
    {
        $this->_amount = $amount;
    }

    public function getAmount()
    {
        return $this->_amount;
    }

    public function loadInvoiceByTransactionId($transactionId)
    {
        foreach ($this->getOrder()->getInvoiceCollection() as $invoice) {
            if ($invoice->getTransactionId() == $transactionId) {
                $invoice->load($invoice->getId()); // to make sure all data will properly load (maybe not required)
                return $invoice;
            }
        }

        return false;
    }

    public function __construct($data) 
    {
        if (strpos(__DIR__, '/Model') !== false) {
            $dir = str_replace('/Model/Refund/Request', '/certificate', __DIR__);
        } else {
            $dir = str_replace('/includes/src', '/app/code/community/TIG/Buckaroo3Extended/certificate', __DIR__);
        }

        define('CERTIFICATE_DIR', $dir);

        $this->setAmount($data['amount']);
        $this->setPayment($data['payment']);
        $this->setOrder($data['payment']->getOrder());
        $this->setSession(Mage::getSingleton('core/session'));

        $invoice = $this->loadInvoiceByTransactionId($this->_getTransactionId());

        if ($invoice === false) {
            Mage::throwException($this->_getHelper()->__('Refund action is not available.'));
        }

        $this->setInvoice($invoice->getId());

        $this->_setOrderBillingInfo();
        $this->setDebugEmail('');

        $this->_checkExpired();

        Mage::dispatchEvent('buckaroo3extended_refund_request_setmethod', array('request' => $this, 'order' => $this->_order));

        $this->setVars(array());
    }

    public function sendRefundRequest()
    {
        try {
            return $this->_sendRefundRequest();
        } catch (Exception $e) {
            Mage::helper('buckaroo3extended')->logException($e);
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function _getTransactionId() 
    {
        if ($this->_payment->getParentTransactionId()) {
            return $this->_payment->getParentTransactionId();
        } elseif ($this->_payment->getLastTransId()) {
            return $this->_payment->getLastTransId();
        }

        return $this->_order->getTransactionKey();
    }

    protected function _sendRefundRequest()
    {
        $this->_debugEmail .= 'Chosen payment method: ' . $this->_method . "\n";

        //if no method has been set (no payment method could identify the chosen method) process the order as if it had failed
        if (empty($this->_method)) {
            $this->_debugEmail .= "No method was set! :( \n";
            Mage::getModel('buckaroo3extended/refund_response_abstract', array('response' => false, 'XML' => false))->processResponse();
        }

        $this->_debugEmail .= "\n";
        //forms an array with all payment-independant variables (such as merchantkey, order id etc.) which are required for the transaction request
        $this->_addBaseVariables();
        $this->_addOrderVariables();
        $this->_addShopVariables();
        $this->_addRefundVariables();
        $this->_addCustomParameters();
        $this->_addSoftwareVariables();

        $this->_debugEmail .= "Firing request events. \n";
        //event that allows individual payment methods to add additional variables such as bankaccount number
        Mage::dispatchEvent('buckaroo3extended_refund_request_addservices', array('request' => $this, 'order' => $this->_order));
        Mage::dispatchEvent('buckaroo3extended_refund_request_addcustomvars', array('request' => $this, 'order' => $this->_order, 'payment' => $this->_payment));

        $this->_debugEmail .= "Events fired! \n";

        //clean the array for a soap request
        $this->setVars($this->_cleanArrayForSoap($this->getVars()));

        $this->_debugEmail .= "Variable array:" . var_export($this->_vars, true) . "\n\n";
        $this->_debugEmail .= "Building SOAP request... \n";

        //send the transaction request using SOAP

        /** @var $soap TIG_Buckaroo3Extended_Model_Soap */
        $soap = Mage::getModel('buckaroo3extended/soap', array('vars' => $this->getVars(), 'method' => $this->getMethod()));
        list($response, $responseXML, $requestXML) = $soap->transactionRequest();

        $this->_debugEmail .= "The SOAP request has been sent. \n";
        if (is_object($requestXML) && is_object($responseXML)) {
            $this->_debugEmail .= "Request: " . var_export($requestXML->saveXML(), true) . "\n";
            $this->_debugEmail .= "Response: " . var_export($response, true) . "\n";
            $this->_debugEmail .= "Response XML:" . var_export($responseXML->saveXML(), true) . "\n\n";
        }

        $this->_debugEmail .= "Response received. \n";
        //process the response

        $processedResponse = Mage::getModel(
            'buckaroo3extended/refund_response_abstract',
            array(
                'response'   => $response,
                'XML'        => $responseXML,
                'debugEmail' => $this->_debugEmail,
                'payment'    => $this->_payment,
                'order'      => $this->_order,
            )
        )->processResponse();

        $this->setPayment($processedResponse->getPayment());

        return $this;
    }

    /**
     * Only difference with parent is that here the totalAmount is 'Credit', rather than 'Debit'
     *
     * @see TIG_Buckaroo3Extended_Model_Request_Abstract::_addOrderVariables()
     */
    protected function _addOrderVariables()
    {
        list($currency, $totalAmount) = $this->_determinRefundAmountAndCurrency();

        $tax = 0;
        foreach($this->_order->getFullTaxInfo() as $taxRecord)
        {
            $tax += $taxRecord['amount'];
        }

        $tax = round($tax, 2);

        $this->_vars['currency']     = $currency;
        $this->_vars['amountCredit'] = $totalAmount;
        $this->_vars['amountDebit']  = 0;
        $this->_vars['orderId']      = $this->_order->getIncrementId();

        $this->_debugEmail .= 'Order variables added! \n';
    }

    protected function _determinRefundAmountAndCurrency()
    {
        $baseCurrency  = $this->_order->getBaseCurrency()->getCode();
        $currency      = $this->_order->getCurrencyCodeUsedForTransaction();

        if ($baseCurrency == $currency) {
            return array($currency, $this->_amount);
        } else {
            $amount = round($this->_amount * $this->_order->getBaseToOrderRate(), 2);
            return array($currency, $amount);
        }
    }

    protected function _addCustomParameters()
    {
        $array = array(
            'refund_initiated_in_magento' => 1,
        );

        if (isset($this->_vars['customParameters'])) {
            $this->_vars['customParameters'] = array_merge($this->_vars['customParameters'], $array);
        } else {
            $this->_vars['customParameters'] = $array;
        }
    }
}
