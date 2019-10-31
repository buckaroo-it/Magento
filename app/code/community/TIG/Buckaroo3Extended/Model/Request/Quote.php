<?php
class TIG_Buckaroo3Extended_Model_Request_Quote extends TIG_Buckaroo3Extended_Model_Request_Abstract
{
    /**
     * @param array $params
     */
    public function __construct($params = array())
    {
        $quote = isset($params['quote']) ? $params['quote'] : null;

        if ($quote instanceof Mage_Sales_Model_Quote) {
            // use quote as order
            $this->setOrder($quote);
            $this->setMethod($quote->getPayment()->getMethodCode());
        }

        parent::__construct();

        // make the response use quote as order
        $this->setResponseModelClass('buckaroo3extended/response_quote');
    }

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
        Mage::dispatchEvent('buckaroo3extended_request_addcustomvars', array('request' => $this, 'order' => $this->_order));
        Mage::dispatchEvent('buckaroo3extended_request_addcustomparameters', array('request' => $this, 'order' => $this->_order));

        $this->_debugEmail .= "Events fired! \n";

        //clean the array for a soap request
        $this->setVars($this->_cleanArrayForSoap($this->getVars()));

        $this->_debugEmail .= "Variable array:" . var_export($this->_vars, true) . "\n\n";
        $this->_debugEmail .= "Building SOAP request... \n";

        //send the transaction request using SOAP
        $soap = Mage::getModel('buckaroo3extended/soap', array('vars' => $this->getVars(), 'method' => $this->getMethod()));
        list($response, $responseXML, $requestXML) = $soap->transactionRequest();

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
     * Overload the cleanup function to convert self into an InitializeCheckout request
     *
     * @param array $array
     * @return array $cleanArray
     */
    public function _cleanArrayForSoap($array)
    {
        // prepare InitializeCheckout request vars
        $array['customVars']['masterpass']['LightboxRequest'] = 'true';
        $array['customVars']['masterpass']['InitializeUrl'] = Mage::app()->getStore()->getCurrentUrl(false);
        $array['services']['masterpass']['action'] = 'InitializeCheckout';

        return parent::_cleanArrayForSoap($array);
    }

    /**
     * Determines the totalamount of the order and the currency to be used based on which currencies are available
     * and which currency the customer has selected.
     *
     * Will default to base currency if the selected currency is unavailable.
     *
     * @return array
     */
    protected function _determineAmountAndCurrency()
    {
        $currenciesAllowed = array('EUR');

        $currentCurrency = Mage::app()->getStore()->getCurrentCurrencyCode();

        if ($this->_order->getIsVirtual()) {
            $address = $this->_order->getBillingAddress();
        } else {
            $address = $this->_order->getShippingAddress();
        }

        // currency is not available for this module
        if (in_array($currentCurrency, $currenciesAllowed)) {
            $currency = $currentCurrency;
            $totalAmount = $address->getSubtotal()
                         + $address->getTaxAmount()
                         - $address->getDiscountAmount();
        } else {
            $totalAmount = $address->getBaseSubtotal()
                         + $address->getBaseTaxAmount()
                         - $address->getBaseDiscountAmount();
            $currency = $this->_order->getBaseCurrency()->getCode();
        }

        return array($currency, $totalAmount);
    }
}
