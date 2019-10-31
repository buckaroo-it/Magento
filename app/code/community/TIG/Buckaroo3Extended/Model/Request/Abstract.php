<?php
class TIG_Buckaroo3Extended_Model_Request_Abstract extends TIG_Buckaroo3Extended_Model_Abstract
{
    protected $_vars;
    protected $_method;
    protected $_responseModelClass;

    public function getVars()
    {
        return $this->_vars;
    }

    public function setVars($vars = array())
    {
        $this->_vars = $vars;

        return $this;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function setMethod($method = '')
    {
        $this->_method = $method;

        return $this;
    }

    public function getResponseModelClass()
    {
        return $this->_responseModelClass;
    }

    public function setResponseModelClass($class = '')
    {
        $this->_responseModelClass = $class;

        return $this;
    }

    public function __construct()
    {
        parent::__construct();

        $this->setVars(array());

        $responseModelClass = Mage::helper('buckaroo3extended')->isAdmin() ? 'buckaroo3extended/response_backendOrder' : 'buckaroo3extended/response_abstract';
        $this->setResponseModelClass($responseModelClass);
    }
    
    /**
     * @return mixed
     * @throws Exception
     */
    public function sendRequest()
    {
        try {
            return $this->_sendRequest();
        } catch (Exception $e) {
            Mage::helper('buckaroo3extended')->logException($e);
            $responseModel = Mage::getModel(
                $this->_responseModelClass, array(
                'response'   => false,
                'XML'        => false,
                'debugEmail' => $this->_debugEmail,
                )
            );
            $responseModel->setOrder($this->_order)
                          ->processResponse();
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * @return mixed
     * @throws Exception
     */
    protected function _sendRequest()
    {
        if (empty($this->_order)) {
            $this->_debugEmail .= "No order was set! :( \n";
            return Mage::getModel(
                $this->_responseModelClass, array(
                'response'   => false,
                'XML'        => false,
                'debugEmail' => $this->_debugEmail,
                )
            )->processResponse();
        }

        if($this->_order->hasTransactionKey()){
            $message = 'Order '.$this->_order->getIncrementId().' has a new transaction key requested, the current key will be unset.'."\n";
            $message.= 'current key: '.$this->_order->getTransactionKey();

            $this->_debugEmail .= $message."\n";

            $this->_order->addStatusHistoryComment(
                Mage::helper('buckaroo3extended')->__('New transaction key requested, the current key is unset.')
            );

            $this->_order->setTransactionKey(false);
            $this->_order->save();
        }

        Mage::dispatchEvent('buckaroo3extended_request_setmethod', array('request' => $this, 'order' => $this->_order));

        $this->_debugEmail .= 'Chosen payment method: ' . $this->_method . "\n";

        //if no method has been set (no payment method could identify the chosen method) process the order as if it had failed
        if (empty($this->_method)) {
            $this->_debugEmail .= "No method was set! :( \n";
            $responseModel = Mage::getModel(
                $this->_responseModelClass, array(
                'response'   => false,
                'XML'        => false,
                'debugEmail' => $this->_debugEmail,
                )
            );
            if (!$responseModel->getOrder()) {
                $responseModel->setOrder($this->_order);
            }

            return $responseModel->processResponse();
        }

        //hack to prevent SQL errors when using onestepcheckout
        if(!Mage::helper('buckaroo3extended')->isAdmin()) {
            Mage::getSingleton('checkout/session')->getQuote()->setReservedOrderId(null)->save();
        }else {
            Mage::getSingleton('adminhtml/session_quote')->getQuote()->setReservedOrderId(null)->save();
        }


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
        /** @var TIG_Buckaroo3Extended_Model_Soap $soap */
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

        try {
            return $responseModel->processResponse();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    protected function _addServices()
    {
        $this->_vars['services'][$this->_method] = array(
            'action'    => 'Pay',
            'version'   => 1,
        );
    }

    protected function _addBaseVariables()
    {
        list($country, $locale, $lang) = $this->_getLocale();

        //test mode can be set in the general config options, but also in the config options for the individual payment options.
        //The latter overwrites the first if set to true
        $test = Mage::getStoreConfig('buckaroo/buckaroo3extended/mode', Mage::app()->getStore()->getStoreId());

        if (!$test && Mage::getStoreConfig('buckaroo/buckaroo3extended' . $this->_code . '/mode', Mage::app()->getStore()->getStoreId())) {
            $test = '1';
        }

        $this->_vars['country']        = $country;
        $this->_vars['locale']         = $locale;
        $this->_vars['lang']           = $lang;
        $this->_vars['test']           = $test;

        $this->_debugEmail .= "Base variables added! \n";
    }

    protected function _addShopVariables()
    {
        $returnUrl = Mage::getUrl(
            'buckaroo3extended/notify/return', array(
            '_secure' => true,
            '_store' => $this->_order->getStoreId(),
            )
        );

        $merchantKey = Mage::getStoreConfig('buckaroo/buckaroo3extended/key', $this->_order->getStoreId());
        $description = Mage::getStoreConfig('buckaroo/buckaroo3extended/payment_description', $this->_order->getStoreId());
        $thumbprint  = Mage::getStoreConfig('buckaroo/buckaroo3extended/thumbprint', $this->_order->getStoreId());

        $this->_vars['returnUrl']      = $returnUrl;
        $this->_vars['merchantKey']    = $merchantKey;
        $this->_vars['description']    = $description;
        $this->_vars['thumbprint']     = $thumbprint;

        $this->_debugEmail .= "Shop variables added! \n";
    }

    protected function _addSoftwareVariables()
    {
        $platformName = 'Magento';

        if (method_exists('Mage', 'getEdition')) {
            $platformName .= ' ' . Mage::getEdition();
        }

        $platformVersion = Mage::getVersion();
        $moduleSupplier = 'Total Internet Group';
        $moduleName = 'Buckaroo3Extended';
        $moduleVersion = (string) Mage::getConfig()->getModuleConfig("TIG_Buckaroo3Extended")->version;

        $array = array(
            'PlatformName'    => $platformName,
            'PlatformVersion' => $platformVersion,
            'ModuleSupplier'  => $moduleSupplier,
            'ModuleName'      => $moduleName,
            'ModuleVersion'   => $moduleVersion,
        );

        $this->_vars['Software'] = $array;

        $this->_debugEmail .= "Software variables added! \n";
    }

    protected function _addOrderVariables()
    {
        list($currency, $totalAmount) = $this->_determineAmountAndCurrency();

        // If we have a quote instead of an order, we need to look elsewhere for the total
        if ($this->_order instanceof Mage_Sales_Model_Quote) {
            $correctAmount = $this->_order->getShippingAddress()->getBaseGrandTotal();
            if ($correctAmount == 0) {
                $correctAmount = $this->_order->getBillingAddress()->getBaseGrandTotal();
            }

            if ($correctAmount > 0) {
                $totalAmount = $correctAmount;
            }
        }

        $this->_vars['currency']     = $currency;
        $this->_vars['amountCredit'] = 0;
        $this->_vars['amountDebit']  = $totalAmount;
        $this->_vars['orderId']      = $this->_order->getIncrementId();

        $this->_debugEmail .= "Order variables added! \n";
    }

    protected function _addRefundVariables()
    {
        $invoice                               = Mage::getModel('sales/order_invoice')->load($this->_invoice);
        $this->_vars['OriginalTransactionKey'] = $invoice->getTransactionId();
        $this->_vars['invoiceId']              = 'CM'.$invoice->getIncrementId();

        $this->_debugEmail                    .= "Refund variables added! \n";
    }

    /**
     * Add variables for Capture requests
     */
    protected function _addCaptureVariables()
    {
        $this->_vars['OriginalTransactionKey'] = $this->_order->getTransactionKey();

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
        $invoiceCollection = $this->_order->getInvoiceCollection();

        /** @var Mage_Sales_Model_Order_Invoice $lastInvoice */
        $lastInvoice = $invoiceCollection->getLastItem();

        if ($this->_currentCurrencyIsAllowed()) {
            $partialAmount = $lastInvoice->getGrandTotal();
        } else {
            $partialAmount = $lastInvoice->getBaseGrandTotal();
        }

        if ($partialAmount < $this->_vars['amountDebit']) {
            $this->_vars['amountDebit'] = $partialAmount;
            $this->_vars['invoiceId']   = $this->_order->getIncrementId() . '-'
                . count($invoiceCollection) . '-' . substr(md5(date("YMDHis")), 0, 6);
        }

        $this->_debugEmail .= "Capture variables added! \n";
    }

    /**
     * Add variables for Cancel Authorize requests.
     * AmountDebit and AmountCredit are swapped since this is not a pay request, but a cancel one.
     */
    protected function _addCancelAuthorizeVariables()
    {
        $currentCurrencyIsAllowed = $this->_currentCurrencyIsAllowed();
        $amountDebit = $this->_order->getBaseGrandTotal();

        if ($currentCurrencyIsAllowed) {
            $amountDebit = $this->_order->getGrandTotal();
        }

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
        $invoiceCollection = $this->_order->getInvoiceCollection();

        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        foreach ($invoiceCollection as $invoice) {
            if ($currentCurrencyIsAllowed) {
                $amountDebit -= $invoice->getGrandTotal();
            } else {
                $amountDebit -= $invoice->getBaseGrandTotal();
            }
        }

        $this->_vars['OriginalTransactionKey'] = $this->_order->getTransactionKey();
        $this->_vars['amountCredit'] = $amountDebit;
        $this->_vars['amountDebit']  = 0;
    }
}
