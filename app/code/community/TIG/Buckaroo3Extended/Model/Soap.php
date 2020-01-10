<?php
/**  ____________  _     _ _ ________  ___  _ _  _______   ___  ___  _  _ _ ___
 *   \_ _/ \_ _/ \| |   |_| \ \_ _/  \| _ || \ |/  \_ _/  / __\| _ |/ \| | | _ \
 *    | | | | | ' | |_  | |   || | '_/|   /|   | '_/| |  | |_ \|   / | | | | __/
 *    |_|\_/|_|_|_|___| |_|_\_||_|\__/|_\_\|_\_|\__/|_|   \___/|_\_\\_/|___|_|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
//@codingStandardsIgnoreFile
final class TIG_Buckaroo3Extended_Model_Soap extends TIG_Buckaroo3Extended_Model_Abstract
{
    const WSDL_URL = 'https://checkout.buckaroo.nl/soap/Soap.svc?singleWsdl';

    protected $_vars;
    protected $_method;

    protected $_debugEmail;

    /**
     * @param array $vars
     */
    public function setVars($vars = array())
    {
        $this->_vars = $vars;
    }

    /**
     * @return mixed
     */
    public function getVars()
    {
        return $this->_vars;
    }

    /**
     * @param array $data
     */
    public function __construct($data = array())
    {
        if (!defined('LIB_DIR')) {
            define(
                'LIB_DIR',
                Mage::getBaseDir()
                . DS
                . 'app'
                . DS
                . 'code'
                . DS
                . 'community'
                . DS
                . 'TIG'
                . DS
                . 'Buckaroo3Extended'
                . DS
                . 'lib'
                . DS
            );
        }

        $this->setVars($data['vars']);
        $this->setMethod($data['method']);
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method = '')
    {
        $this->_method = $method;
    }

    protected function getClient()
    {
        try
        {
            //first attempt: use the cached WSDL
            $client = Mage::getModel(
                'buckaroo3extended/soap_clientWSSEC',
                array(
                    'wsdl' => self::WSDL_URL,
                    'options' => array('trace' => 1, 'cache_wsdl' => WSDL_CACHE_DISK)
                )
            );
        } catch (SoapFault $e) {
            try {
                //second attempt: use an uncached WSDL
                //@codingStandardsIgnoreLine
                ini_set('soap.wsdl_cache_ttl', 1);
                $client = Mage::getModel(
                    'buckaroo3extended/soap_clientWSSEC',
                    array(
                        'wsdl' => self::WSDL_URL,
                        'options' => array('trace' => 1, 'cache_wsdl' => WSDL_CACHE_NONE)
                    )
                );
            } catch (SoapFault $e) {
                try {
                    //third and final attempt: use the supplied wsdl found in the lib folder
                    $client = Mage::getModel(
                        'buckaroo3extended/soap_clientWSSEC',
                        array(
                            'wsdl' => LIB_DIR . 'Buckaroo.wsdl',
                            'options' => array('trace' => 1, 'cache_wsdl' => WSDL_CACHE_NONE)
                        )
                    );
                } catch (SoapFault $e) {
                    $client = null;
                }
            }
        }

        return $client;
    }

    /**
     * @return TIG_Buckaroo3Extended_Model_Soap_Body
     */
    protected function getTransactionRequest()
    {
        $transactionRequest = Mage::getModel('buckaroo3extended/soap_body');
        $transactionRequest->Currency = $this->_vars['currency'];

        if (isset($this->_vars['amountDebit'])) {
            $transactionRequest->AmountDebit = round($this->_vars['amountDebit'], 2);
        }

        if (isset($this->_vars['amountCredit'])) {
            $transactionRequest->AmountCredit = round($this->_vars['amountCredit'], 2);
        }

        if (isset($this->_vars['amount'])) {
            $transactionRequest->Amount = round($this->_vars['amount'], 2);
        }

        $invoiceNumber = $this->_vars['orderId'];

        if (isset($this->_vars['invoiceId'])) {
            $invoiceNumber = $this->_vars['invoiceId'];
        }

        $transactionRequest->Invoice = $invoiceNumber;
        $transactionRequest->Order = $this->_vars['orderId'];
        $transactionRequest->Description = $this->_vars['description'];
        $transactionRequest->ReturnURL = $this->_vars['returnUrl'];
        $transactionRequest->StartRecurrent = FALSE;

        if (isset($this->_vars['customVars']['servicesSelectableByClient'])
            && isset($this->_vars['customVars']['continueOnImcomplete'])) {
            $transactionRequest->ServicesSelectableByClient = $this->_vars['customVars']['servicesSelectableByClient'];
            $transactionRequest->ContinueOnIncomplete       = $this->_vars['customVars']['continueOnImcomplete'];
        }

        if (array_key_exists('OriginalTransactionKey', $this->_vars)) {
            $transactionRequest->OriginalTransactionKey = $this->_vars['OriginalTransactionKey'];
        }

        if (!empty($this->_vars['request_type'])
            && $this->_vars['request_type'] == 'CancelTransaction'
            && !empty($this->_vars['TransactionKey'])
        ) {
            $transactionParameter = Mage::getModel('buckaroo3extended/soap_requestParameter');
            $transactionParameter->Key = $this->_vars['TransactionKey'];
            $transactionRequest->Transaction = $transactionParameter;
        }

        if (isset($this->_vars['customParameters'])) {
            $transactionRequest = $this->_addCustomParameters($transactionRequest);
        }

        $transactionRequest->Services = Mage::getModel('buckaroo3extended/soap_services');

        $this->_addServices($transactionRequest);

        $transactionRequest->ClientIP = Mage::getModel('buckaroo3extended/soap_iPAddress');
        $transactionRequest->ClientIP->Type = 'IPv4';
        $transactionRequest->ClientIP->_ = Mage::helper('core/http')->getRemoteAddr();

        return $transactionRequest;
    }

    /**
     * @return array
     */
    public function transactionRequest()
    {
        $client = $this->getClient();

        if ($client === null) {
            return $this->_error();
        }

        /*when request is a refund; use 'CallCenter' else use channel 'Web' (case sensitive)*/
        $requestChannel = 'Web';

        if (isset($this->_vars['invoiceId'])
            && round($this->_vars['amountDebit'], 2) == 0
            && round($this->_vars['amountCredit'], 2) > 0) {
            $requestChannel = 'CallCenter';
        }

        // The channel set in the vars takes precedence over the above condition
        if (isset($this->_vars['channel'])) {
            $requestChannel = $this->_vars['channel'];
        }

        $client->thumbprint = $this->_vars['thumbprint'];

        // Get the order so we can get the storeId relevant for this order
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($this->_vars['orderId'], 'increment_id');
        // And pass the storeId to the WSDL client
        $client->storeId = $order->getStoreId();

        $transactionRequest = $this->getTransactionRequest();

        $software = Mage::getModel('buckaroo3extended/soap_software');
        $software->PlatformName = $this->_vars['Software']['PlatformName'];
        $software->PlatformVersion = $this->_vars['Software']['PlatformVersion'];
        $software->ModuleSupplier = $this->_vars['Software']['ModuleSupplier'];
        $software->ModuleName = $this->_vars['Software']['ModuleName'];
        $software->ModuleVersion = $this->_vars['Software']['ModuleVersion'];

        $header = Mage::getModel('buckaroo3extended/soap_header');
        $header->MessageControlBlock = Mage::getModel('buckaroo3extended/soap_messageControlBlock');
        $header->MessageControlBlock->Id = '_control';
        $header->MessageControlBlock->WebsiteKey = $this->_vars['merchantKey'];
        $header->MessageControlBlock->Culture = $this->_vars['locale'];
        $header->MessageControlBlock->TimeStamp = time();
        $header->MessageControlBlock->Channel = $requestChannel;
        $header->MessageControlBlock->Software = $software;
        $header->Security = Mage::getModel('buckaroo3extended/soap_securityType');
        $header->Security->Signature = $oldclassobject = Mage::getModel('buckaroo3extended/soap_signatureType');

        $canonicalizationMethod = Mage::getModel('buckaroo3extended/soap_methodType');
        $canonicalizationMethod->Algorithm = 'http://www.w3.org/2001/10/xml-exc-c14n#';
        $signatureMethod = Mage::getModel('buckaroo3extended/soap_methodType');
        $signatureMethod->Algorithm = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

        $header->Security->Signature->SignedInfo = Mage::getModel('buckaroo3extended/soap_signedInfoType');
        $header->Security->Signature->SignedInfo->CanonicalizationMethod = $canonicalizationMethod;
        $header->Security->Signature->SignedInfo->SignatureMethod = $signatureMethod;

        $reference = Mage::getModel('buckaroo3extended/soap_referenceType');
        $reference->URI = '#_body';
        $transform = Mage::getModel('buckaroo3extended/soap_methodType');
        $transform->Algorithm = 'http://www.w3.org/2001/10/xml-exc-c14n#';
        $reference->Transforms=array($transform);

        $reference->DigestMethod = Mage::getModel('buckaroo3extended/soap_methodType');
        $reference->DigestMethod->Algorithm = 'http://www.w3.org/2000/09/xmldsig#sha1';
        $reference->DigestValue = '';

        $transformTwo = Mage::getModel('buckaroo3extended/soap_methodType');
        $transformTwo->Algorithm = 'http://www.w3.org/2001/10/xml-exc-c14n#';
        $referenceControl = Mage::getModel('buckaroo3extended/soap_referenceType');
        $referenceControl->URI = '#_control';
        $referenceControl->DigestMethod = Mage::getModel('buckaroo3extended/soap_methodType');
        $referenceControl->DigestMethod->Algorithm = 'http://www.w3.org/2000/09/xmldsig#sha1';
        $referenceControl->DigestValue = '';
        $referenceControl->Transforms=array($transformTwo);

        $header->Security->Signature->SignedInfo->Reference = array($reference,$referenceControl);
        $header->Security->Signature->SignatureValue = '';

        $soapHeaders = array();
        $soapHeaders[] = new SOAPHeader(
            'https://checkout.buckaroo.nl/PaymentEngine/',
            'MessageControlBlock',
            $header->MessageControlBlock
        );
        $soapHeaders[] = new SOAPHeader(
            'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
            'Security',
            $header->Security
        );
        $client->__setSoapHeaders($soapHeaders);

        //if the module is set to testmode, use the test gateway. Otherwise, use the default gateway
        $location = 'https://checkout.buckaroo.nl/soap/';
        $mode = Mage::getStoreConfig('buckaroo/buckaroo3extended/mode', Mage::app()->getStore()->getStoreId());
        $methodMode = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_' . $this->_method . '/mode',
            Mage::app()->getStore()->getStoreId()
        );

        if ($mode || $methodMode) {
            $location = 'https://testcheckout.buckaroo.nl/soap/';
        }

        $client->__SetLocation($location);

        $requestType = 'TransactionRequest';
        if (!empty($this->_vars['request_type'])) {
            $requestType = $this->_vars['request_type'];
        }

        try {
            $response = $client->$requestType($transactionRequest);
        } catch (Exception $e) {
            $this->logException($e->getMessage());
            return $this->_error($client);
        }

        $response->requestType = $requestType;

        if (null === $response) {
            $response = false;
        }

        $responseXML = $client->__getLastResponse();
        $requestXML = $client->__getLastRequest();

        $responseDomDOC = new DOMDocument();
        $responseDomDOC->loadXML($responseXML);
        $responseDomDOC->preserveWhiteSpace = FALSE;
        $responseDomDOC->formatOutput = TRUE;

        $requestDomDOC = new DOMDocument();
        $requestDomDOC->loadXML($requestXML);
        $requestDomDOC->preserveWhiteSpace = FALSE;
        $requestDomDOC->formatOutput = TRUE;

        return array($response, $responseDomDOC, $requestDomDOC);
    }

    /**
     * @param SoapClientWSSEC|bool $client
     *
     * @return array
     */
    protected function _error($client = false)
    {
        $response = false;

        $responseDomDOC = new DOMDocument();
        $requestDomDOC = new DOMDocument();
        if ($client) {
            $responseXML = $client->__getLastResponse();
            $requestXML = $client->__getLastRequest();

            if (!empty($responseXML)) {
                $responseDomDOC->loadXML($responseXML);
                $responseDomDOC->preserveWhiteSpace = FALSE;
                $responseDomDOC->formatOutput = TRUE;
            }

            if (!empty($requestXML)) {
                $requestDomDOC->loadXML($requestXML);
                $requestDomDOC->preserveWhiteSpace = FALSE;
                $requestDomDOC->formatOutput = TRUE;
            }
        }

        return array($response, $responseDomDOC, $requestDomDOC);
    }

    /**
     * @param $TransactionRequest
     */
    protected function _addServices(&$transactionRequest)
    {
        if (!is_array($this->_vars['services']) || empty($this->_vars['services'])) {
            return;
        }

        $services = array();
        foreach ($this->_vars['services'] as $fieldName => $value) {
            if (empty($value)) {
                continue;
            }

            $service = Mage::getModel('buckaroo3extended/soap_service');
            $name = $fieldName;

            if (isset($value['name'])){
                $name = $value['name'];
            }

            $service->Name    = $name;
            $service->Action  = $value['action'];
            $service->Version = $value['version'];

            $this->_addCustomFields($service, $fieldName);

            $services[] = $service;
        }

        $transactionRequest->Services->Service = $services;
    }

    /**
     * @param $service
     * @param $name
     */
    protected function _addCustomFields(&$service, $name)
    {
        if (!isset($this->_vars['customVars'])
            || !isset($this->_vars['customVars'][$name])
            || empty($this->_vars['customVars'][$name])
        ) {
            unset($service->RequestParameter);
            return;
        }

        $requestParameters = array();

        foreach ($this->_vars['customVars'][$name] as $fieldName => $value) {
            if ($fieldName === 'Articles' && is_array($value) && !empty($value)) {
                foreach ($value as $groupId => $articleArray) {
                    if (!is_array($articleArray) || empty($articleArray)) {
                        continue;
                    }

                    foreach ($articleArray as $articleName => $articleValue) {
                        $newParameter          = Mage::getModel('buckaroo3extended/soap_requestParameter');
                        $newParameter->Name    = isset($articleValue['name']) ? $articleValue['name'] : $articleName;
                        $newParameter->GroupID = isset($articleValue['groupId']) ? $articleValue['groupId'] : $groupId;
                        $newParameter->Group   = isset($articleValue['group']) ? $articleValue['group'] : "Article";
                        $newParameter->_       = $articleValue['value'];
                        $requestParameters[]   = $newParameter;
                    }
                }

                continue;
            }

            if ((null === $value || $value === '')
                || (
                    is_array($value)
                    && (null === $value['value'] || $value['value'] === '')
                )
            ) {
                continue;
            }

            $requestParameters[] = $this->getCustomFieldRequestParameter($fieldName, $value);
        }

        $service->RequestParameter = $requestParameters;

        if (empty($requestParameters)) {
            unset($service->RequestParameter);
            return;
        }
    }

    /**
     * @param $fieldName
     * @param $value
     *
     * @return TIG_Buckaroo3Extended_Model_Soap_RequestParameter
     */
    protected function getCustomFieldRequestParameter($fieldName, $value)
    {
        $requestParameter = Mage::getModel('buckaroo3extended/soap_requestParameter');
        $requestParameter->Name = $fieldName;
        $requestParameter->_ = $value;

        if (is_array($value)) {
            $requestParameter->Group = $value['group'];
            $requestParameter->_ = $value['value'];

            if (isset($value['groupId']) && !empty($value['groupId'])) {
                $requestParameter->GroupID = $value['groupId'];
            }

            if (isset($value['name']) && !empty($value['name'])) {
                $requestParameter->Name = $value['name'];
            }
        }

        return $requestParameter;
    }

    /**
     * @param $transactionRequest
     *
     * @return mixed
     */
    protected function _addCustomParameters(&$transactionRequest)
    {
        $requestParameters = array();
        foreach ($this->_vars['customParameters'] as $fieldName => $value) {
            if ((null === $value || $value === '')
                || (
                    is_array($value)
                    && (null === $value['value'] || $value['value'] === '')
                )
            ) {
                continue;
            }

            $requestParameter = Mage::getModel('buckaroo3extended/soap_requestParameter');
            $requestParameter->Name = $fieldName;
            if (is_array($value)) {
                $requestParameter->Group = $value['group'];
                $requestParameter->_ = $value['value'];

                if (isset($value['groupId']) && !empty($value['groupId'])) {
                    $requestParameter->GroupID = $value['groupId'];
                }

                if (isset($value['name']) && !empty($value['name'])) {
                    $requestParameter->Name = $value['name'];
                }
            } else {
                $requestParameter->_ = $value;
            }

            $requestParameters[] = $requestParameter;
        }

        if (empty($requestParameters)) {
            unset($transactionRequest->AdditionalParameters);
            return;
        } else {
            $transactionRequest->AdditionalParameters = $requestParameters;
        }

        return $transactionRequest;
    }
}
