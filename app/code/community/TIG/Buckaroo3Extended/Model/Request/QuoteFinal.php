<?php
class TIG_Buckaroo3Extended_Model_Request_QuoteFinal extends TIG_Buckaroo3Extended_Model_Request_Abstract
{
    /**
     * Overload the request function to avoid order transaction key calls on the quote
     */
    protected function _sendRequest()
    {
        Mage::dispatchEvent('buckaroo3extended_request_setmethod', array('request' => $this, 'order' => $this->_order));

        $this->_debugEmail .= 'Chosen payment method: ' . $this->_method . "\n";

        $this->_debugEmail .= "\n";
        //forms an array with all payment-independant variables (such as merchantkey, order id etc.) which are required for the transaction request
        $this->_addBaseVariables();
        $this->_addOrderVariables();
        $this->_addShopVariables();
        $this->_addSoftwareVariables();

        $this->_debugEmail .= "Firing request events. \n";
        //event that allows individual payment methods to add additional variables such as bankaccount number
        Mage::dispatchEvent('buckaroo3extended_request_addservices', array('request' => $this, 'order' => $this->_order));

        /**
         * This needs to be added after the events, as it will overwrite the service action.
         */
        $this->_addQuoteFinalVariables();
        $this->_debugEmail .= "Events fired! \n";

        //clean the array for a soap request
        $this->setVars($this->_cleanArrayForSoap($this->getVars()));

        $this->_debugEmail .= "Variable array:" . var_export($this->_vars, true) . "\n\n";
        $this->_debugEmail .= "Building SOAP request... \n";

        //send the transaction request using SOAP
        $soap = Mage::getModel('buckaroo3extended/soap', array('vars' => $this->getVars(), 'method' => $this->getMethod()));
        list($response, $responseXML, $requestXML) = $soap->transactionRequest();

        //and reload the order
        $this->_order = Mage::getModel('sales/order')->load($this->_order->getId());

        $this->_order->setTransactionKey(Mage::getSingleton('checkout/session')->getBuckarooMasterPassTrx())->save();

        $this->_debugEmail .= "The SOAP request has been sent. \n";

        if (!is_object($requestXML) || !is_object($responseXML)) {
            $this->_debugEmail .= "Request or response was not an object \n";
        } else {
            $this->_debugEmail .= "Request: " . var_export($requestXML->saveXML(), true) . "\n";
            $this->_debugEmail .= "Response: " . var_export($response, true) . "\n";
            $this->_debugEmail .= "Response XML:" . var_export($responseXML->saveXML(), true) . "\n\n";
        }

        $this->_debugEmail .= "Processing response... \n";

        //process the response
        $responseModel = Mage::getModel(
            $this->_responseModelClass, array(
            'response'   => $response,
            'XML'        => $responseXML,
            'debugEmail' => $this->_debugEmail,
            )
        );

        if (!$responseModel->getOrder()) {
            $responseModel->setOrder($this->_order);
        }

        return $responseModel->processResponse();
    }

    /**
     * Add specific variables for the quote finalization.
     */
    protected function _addQuoteFinalVariables()
    {
        $this->_vars['services']['masterpass']['action'] = 'FinalizeCheckout';

        $originalTrx = Mage::getSingleton('checkout/session')->getBuckarooMasterPassTrx();
        $this->_vars['OriginalTransactionKey'] = $originalTrx;
        $this->_vars['request_type'] = 'DataRequest';
    }

    protected function _addOrderVariables()
    {
        list($currency, $totalAmount) = $this->_determineAmountAndCurrency();

        $this->_vars['currency'] = $currency;
        $this->_vars['amount']   = $totalAmount;

        $this->_debugEmail .= "Order variables added! \n";
    }
}
