<?php
class TIG_Buckaroo3Extended_Model_Response_Abstract extends TIG_Buckaroo3Extended_Model_Abstract
{
    protected $_debugEmail = '';
    protected $_responseXML = '';
    protected $_response = '';

    protected $_customResponseProcessing = false;

    const BUCK_RESPONSE_PAYMENT_FAILURE      = 'buckaroo/buckaroo3extended_response/response_payment_failure';
    const BUCK_RESPONSE_VALIDATION_ERROR     = 'buckaroo/buckaroo3extended_response/response_validation_error';
    const BUCK_RESPONSE_TECHNICAL_ERROR      = 'buckaroo/buckaroo3extended_response/response_technical_error';
    const BUCK_RESPONSE_PAYMENT_REJECTED     = 'buckaroo/buckaroo3extended_response/response_payment_rejected';
    const BUCK_RESPONSE_CANCELED_BY_USER     = 'buckaroo/buckaroo3extended_response/response_canceled_by_user';
    const BUCK_RESPONSE_CANCELED_BY_MERCHANT = 'buckaroo/buckaroo3extended_response/response_canceled_by_merchant';
    const BUCK_RESPONSE_DEFAUL_MESSAGE       = 'buckaroo/buckaroo3extended_response/response_default';


    public function setCurrentOrder($order)
    {
        $this->_order = $order;
    }

    public function getCurrentOrder()
    {
        return $this->_order;
    }

    public function setDebugEmail($debugEmail)
    {
        $this->_debugEmail = $debugEmail;
    }

    public function getDebugEmail()
    {
        return $this->_debugEmail;
    }

    public function setResponseXML($xml)
    {
        $this->_responseXML = $xml;
    }

    public function getResponseXML()
    {
        return $this->_responseXML;
    }

    public function setResponse($response)
    {
        $this->_response = $response;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function setCustomResponseProcessing($boolean)
    {
        $this->_customResponseProcessing = (bool) $boolean;
    }

    public function getCustomResponseProcessing()
    {
        return $this->_customResponseProcessing;
    }

    public function __construct($data)
    {
        parent::__construct($data['debugEmail']);
        $this->setResponse($data['response']);
        $this->setResponseXML($data['XML']);
    }
    
    /**
     * @throws Exception
     */
    public function processResponse()
    {
        if ($this->_response === false) {
            $this->_debugEmail .= "An error occurred in building or sending the SOAP request.. \n";
            return $this->_error();
        }

        $this->_debugEmail .= "verifiying authenticity of the response... \n";
        $verified = $this->_verifyResponse();

        if ($verified !== true) {
            $this->_debugEmail .= "The authenticity of the response could NOT be verified. \n";
            return $this->_verifyError();
        }

        $this->_debugEmail .= "Verified as authentic! \n\n";

        if (!$this->_order->getTransactionKey()
            && is_object($this->_response)
            && isset($this->_response->Key))
        {
            $this->_order->setTransactionKey($this->_response->Key);
            $this->_order->save();
            $this->_debugEmail .= 'Transaction key saved: ' . $this->_response->Key . "\n";
        }

        //sets the currency used by Buckaroo
        if (!$this->_order->getCurrencyCodeUsedForTransaction()
            && is_object($this->_response)
            && isset($this->_response->Currency))
        {
            $this->_order->setCurrencyCodeUsedForTransaction($this->_response->Currency);
            $this->_order->save();
        }

        if (is_object($this->_response) && isset($this->_response->RequiredAction)) {
            $requiredAction = $this->_response->RequiredAction->Type;
        } else {
            $requiredAction = false;
        }

        $parsedResponse = $this->_parseResponse();
        $this->_addSubCodeComment($parsedResponse);

        if (!is_null($requiredAction)
            && $requiredAction !== false
            && $requiredAction == 'Redirect')
        {
            $this->_debugEmail .= "Redirecting customer... \n";
            return $this->_redirectUser();
        }

        $this->_debugEmail .= "Parsed response: " . var_export($parsedResponse, true) . "\n";

        $this->_debugEmail .= "Dispatching custom order processing event... \n";
        Mage::dispatchEvent(
            'buckaroo3extended_response_custom_processing',
            array(
                'model'          => $this,
                'order'          => $this->getOrder(),
                'response'       => $parsedResponse,
                'responseobject' => $this->_response,
            )
        );
    
        try {
            return $this->_requiredAction($parsedResponse);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * @param $response
     *
     * @throws Exception
     */
    protected function _requiredAction($response)
    {
        try {
            switch ($response['status']) {
                case self::BUCKAROO_SUCCESS:
                    return $this->_success();
                case self::BUCKAROO_FAILED:
                    return $this->_failed($response['message']);
                case self::BUCKAROO_ERROR:
                    return $this->_error($response['message']);
                case self::BUCKAROO_NEUTRAL:
                    return $this->_neutral();
                case self::BUCKAROO_PENDING_PAYMENT:
                    return $this->_pendingPayment();
                case self::BUCKAROO_INCORRECT_PAYMENT:
                    return $this->_incorrectPayment($response['message']);
                case self::BUCKAROO_REJECTED:
                    return $this->_rejected($response['message']);
                default:
                    return $this->_neutral();
                }
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
    }

    protected function _addSubCodeComment($parsedResponse)
    {
        if (!isset($parsedResponse['subCode'])) {
            return $this;
        }

        $subCode = $parsedResponse['subCode'];

        $this->_order->addStatusHistoryComment(
            Mage::helper('buckaroo3extended')->__(
                'Buckaroo has sent the following response: %s',
                $subCode['message']
            )
        );

        $this->_order->save();
        return $this;
    }

    protected function _redirectUser()
    {
        $redirectUrl = $this->_response->RequiredAction->RedirectURL;

        $this->_order->addStatusHistoryComment(
            Mage::helper('buckaroo3extended')->__(
                'Customer is being redirected to Buckaroo. Url: %s',
                $redirectUrl
            )
        );
        $this->_order->save();

        $this->_debugEmail .= "Redirecting user to…" . $redirectUrl . "\n";

        $this->sendDebugEmail();

        Mage::app()->getResponse()->clearHeaders();
        Mage::app()->getResponse()->setRedirect($redirectUrl)->sendResponse();

        return;
    }

    /**
     * @param string $status
     */
    protected function _success($status = self::BUCKAROO_SUCCESS)
    {
        $this->_debugEmail .= "The response indicates a successful request. \n";

        $this->_order->addStatusHistoryComment(
            Mage::helper('buckaroo3extended')->__(
                'The payment request has been successfully received by Buckaroo.'
            )
        );
        $this->_order->save();

        /**
         * @var Mage_Sales_Model_Order_Payment $payment
         */
        $payment = $this->_order->getPayment();
        $payment->registerAuthorizationNotification($this->_order->getBaseGrandTotal());
        $paymentMethodInstance = $payment->getMethodInstance();
        $paymentMethodInstance->saveAdditionalData($this->_response);

        $shouldSend = Mage::getStoreConfig('buckaroo/' . $payment->getMethod() . '/order_email', $this->_order->getStoreId());

        /**
         * Only send order confirmation email when payment status allows it.
         */
        if (!$paymentMethodInstance->shouldSendOrderConfirmEmailForStatus($status)) {
            $shouldSend = false;
        }

        if(!$this->_order->getEmailSent() && false != $shouldSend)
        {
            $this->_debugEmail .= "New Order email has been send \n";
            $this->sendNewOrderEmail();
        }

        $this->emptyCart();

        Mage::getSingleton('core/session')->addSuccess(
            Mage::helper('buckaroo3extended')->__('Your order has been placed succesfully.')
        );

        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/success_redirect', $this->_order->getStoreId());
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        $this->_debugEmail .= 'Redirecting user to...' . $returnUrl . "\n";

        $this->sendDebugEmail();

        Mage::app()->getResponse()->clearHeaders();
        Mage::app()->getResponse()->setRedirect($returnUrl)->sendResponse();

        return;
    }
    
    /**
     * @param string $message
     *
     * @throws Exception
     */
    protected function _failed($message = '')
    {
        $this->_debugEmail .= 'The transaction was unsuccessful. \n';

        $this->_order->addStatusHistoryComment(
            Mage::helper('buckaroo3extended')->__(
                'The payment request has been denied by Buckaroo.'
            )
        );
        $this->_order->save();

        $this->restoreQuote();

        $parsedResponse = $this->_parseResponse();
        $billingCountry = $this->_order->getBillingAddress()->getCountry();

        $errorMessage = $this->_getCorrectFailureMessage($message);

        if ($billingCountry == 'NL' && isset($parsedResponse['code']) && $parsedResponse['code'] == 490) {
            $responseErrorMessage = $this->getResponseFailureMessage();
            $errorMessage = strlen($responseErrorMessage) > 0 ? $responseErrorMessage : $errorMessage;
        }

        Mage::getSingleton('core/session')->addError($errorMessage);

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/cancel_on_failed', $this->_order->getStoreId())) {
            $this->_returnGiftcards($this->_order);
            $this->setBuckarooFailedAuthorize();
            $this->_order->cancel()->save();
        }

        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/failure_redirect', $this->_order->getStoreId());
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        $this->_debugEmail .= 'Redirecting user to...' . $returnUrl . "\n";

        $this->sendDebugEmail();
        
        $payment = $this->_order->getPayment();
        if($payment->getMethod() == 'buckaroo3extended_applepay') {
            throw new Exception('The payment request has been denied by Buckaroo.');
        }

        Mage::app()->getResponse()->clearHeaders();
        Mage::app()->getResponse()->setRedirect($returnUrl)->sendResponse();

        return;
    }
    
    /**
     * @param string $message
     *
     * @throws Exception
     */
    protected function _error($message = '')
    {
        $this->_debugEmail .= "The transaction generated an error. \n";

        Mage::getSingleton('core/session')->addError(
            $this->_getCorrectFailureMessage($message)
        );

        $this->_order->addStatusHistoryComment(
            Mage::helper('buckaroo3extended')->__(
                'A technical error has occurred.'
            )
        );

        $this->_returnGiftcards($this->_order);
        $this->setBuckarooFailedAuthorize();
        $this->_order->cancel()->save();


        $this->_debugEmail .= "The order has been cancelled. \n";
        $this->restoreQuote();
        $this->_debugEmail .= "The quote has been restored. \n";

        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/failure_redirect', $this->_order->getStoreId());
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        $this->_debugEmail .= 'Redirecting user to...' . $returnUrl . "\n";

        $this->sendDebugEmail();
    
        $payment = $this->_order->getPayment();
        if($payment->getMethod() == 'buckaroo3extended_applepay') {
            throw new Exception('A technical error has occurred.');
        }
        
        Mage::app()->getResponse()->clearHeaders();
        Mage::app()->getResponse()->setRedirect($returnUrl)->sendResponse();

        return;
    }
    
    /**
     * @param string $message
     *
     * @throws Mage_Core_Exception
     */
    protected function _rejected($message = '')
    {

        $this->_debugEmail .= "The transaction generated an error. \n";

        $paymentInstance = $this->_order->getPayment()->getMethodInstance();
        $rejectedMessage = $paymentInstance->getRejectedMessage($this->_response);

        if ($rejectedMessage == false || strlen($rejectedMessage) <= 0) {
            $rejectedMessage = $this->_getCorrectFailureMessage($message);
        }

        $rejectedMessage = Mage::helper('buckaroo3extended')->__($rejectedMessage);

        Mage::getSingleton('core/session')->addError($rejectedMessage);

        $this->_returnGiftcards($this->_order);
        $this->setBuckarooFailedAuthorize();
        $this->_order->cancel()->save();
        
        $this->_debugEmail .= "The order has been cancelled. \n";
        $this->restoreQuote();
        $this->_debugEmail .= "The quote has been restored. \n";

        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/failure_redirect', $this->_order->getStoreId());
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        $this->_debugEmail .= 'Redirecting user to...' . $returnUrl . "\n";

        $this->sendDebugEmail();
    
        $payment = $this->_order->getPayment();
        if($payment->getMethod() == 'buckaroo3extended_applepay') {
            throw new Exception('The transaction generated an error.');
        }

        Mage::app()->getResponse()->clearHeaders();
        Mage::app()->getResponse()->setRedirect($returnUrl)->sendResponse();

        return;
    }

    protected function _neutral()
    {
        $this->_debugEmail .= "The response is neutral (not successful, not unsuccessful). \n";

        $this->_order->addStatusHistoryComment(
            Mage::helper('buckaroo3extended')->__(
                'The payment request has been received by Buckaroo.'
            )
        );
        $this->_order->save();

        Mage::getSingleton('core/session')->addSuccess(
            Mage::helper('buckaroo3extended')->__(
                'Your order has been placed succesfully. You will receive an e-mail containing further payment instructions shortly.'
            )
        );

        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/success_redirect', $this->_order->getStoreId());
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        $this->_debugEmail .= 'Redirecting user to...' . $returnUrl . '\n';

        $this->sendDebugEmail();

        Mage::app()->getResponse()->clearHeaders();
        Mage::app()->getResponse()->setRedirect($returnUrl)->sendResponse();

        return;
    }

    /**
     * @param $message
     *
     * @return string
     */
    protected function _getCorrectFailureMessage($message)
    {
        switch ($message) {
            case 'Payment failure' :
                return Mage::helper('buckaroo3extended')->__(
                    Mage::getStoreConfig(
                        self::BUCK_RESPONSE_PAYMENT_FAILURE,
                        $this->_order->getStoreId()
                    )
                );
                break;
            case 'Validation error' :
                return Mage::helper('buckaroo3extended')->__(
                    Mage::getStoreConfig(
                        self::BUCK_RESPONSE_VALIDATION_ERROR,
                        $this->_order->getStoreId()
                    )
                );
                break;
            case 'Technical error' :
                return Mage::helper('buckaroo3extended')->__(
                    Mage::getStoreConfig(
                        self::BUCK_RESPONSE_TECHNICAL_ERROR,
                        $this->_order->getStoreId()
                    )
                );
                break;
            case 'Payment rejected' :
                return Mage::helper('buckaroo3extended')->__(
                    Mage::getStoreConfig(
                        self::BUCK_RESPONSE_PAYMENT_REJECTED,
                        $this->_order->getStoreId()
                    )
                );
                break;
            case 'Cancelled by consumer' :
                return Mage::helper('buckaroo3extended')->__(
                    Mage::getStoreConfig(
                        self::BUCK_RESPONSE_CANCELED_BY_USER,
                        $this->_order->getStoreId()
                    )
                );
                break;
            case 'Cancelled by merchant' :
                return Mage::helper('buckaroo3extended')->__(
                    Mage::getStoreConfig(
                        self::BUCK_RESPONSE_CANCELED_BY_MERCHANT,
                        $this->_order->getStoreId()
                    )
                );
                break;
            default :
                return Mage::helper('buckaroo3extended')->__(
                    Mage::getStoreConfig(
                        self::BUCK_RESPONSE_DEFAUL_MESSAGE,
                        $this->_order->getStoreId()
                    )
                );
        }
    }

    /**
     * Set additional data to payment to NOT sent Buckaroo a CancelAuthorize or CancelReservation.
     * "Do not cancel order on a failed authorize, because it will send a cancel authorize message to
     * Buckaroo, this is not needed/correct."
     */
    protected function setBuckarooFailedAuthorize()
    {
        $setFailedAuthorize = false;

        //Afterpay
        if (isset($this->_response->TransactionType) && $this->_response->TransactionType == 'I013') {
            $setFailedAuthorize = true;
        }

        //Klarna
        if (isset($this->_response->requestType) && $this->_response->requestType == 'DataRequest' &&
            isset($this->_response->ServiceCode) && $this->_response->ServiceCode == 'klarna' &&
            isset($this->_response->Status->Code->Code) && $this->_response->Status->Code->Code == '490' ) {
            $setFailedAuthorize = true;
        }
 
        if ($setFailedAuthorize) {
            $payment = $this->_order->getPayment();
            $payment->setAdditionalInformation('buckaroo_failed_authorize', 1);
            $payment->save();
        }
    }

    /**
     * @return null|string
     */
    private function getResponseFailureMessage()
    {
        $serviceCode = $this->_response->ServiceCode;

        switch ($serviceCode) {
            case 'afterpaydigiaccept':
            case 'afterpayacceptgiro':
                $transactionType = $this->_response->TransactionType;
                $failureMessage = null;

                //Only specific Afterpay responses have a custom response message
                if ($transactionType == 'C011' || $transactionType == 'C016') {
                    $parsedResponse = $this->_parseResponse();
                    $subcodeMessage = explode(':', $parsedResponse['subCode']['message']);

                    if (count($subcodeMessage) > 1) {
                        array_shift($subcodeMessage);
                    }

                    $failureMessage = trim(implode(':', $subcodeMessage));
                }
                break;
            case 'klarna':
                $failureMessage = $this->_response->ConsumerMessage->HtmlText;
                break;
            default:
                $failureMessage = null;
                break;
        }

        return $failureMessage;
    }

    /**
     * return the giftcard amount, if there is one
     * @param $order Mage_Sales_Model_Order
     */
    protected function _returnGiftcards($order)
    {
        if((float)$order->getGiftCardsAmount() > 0){
            $this->_revertGiftCardsForOrder($order);
        }
    }

    /**
     * Revert authorized amounts for all order's gift cards
     *
     * @param   Mage_Sales_Model_Order $order
     * @return  Enterprise_GiftCardAccount_Model_Observer
     */
    protected function _revertGiftCardsForOrder(Mage_Sales_Model_Order $order)
    {
        $cards = Mage::helper('enterprise_giftcardaccount')->getCards($order);
        if (is_array($cards)) {
            foreach ($cards as $card) {
                if (isset($card['authorized'])) {
                    $this->_revertById($card['i'], $card['authorized']);
                }
            }
        }

        return $this;
    }

    /**
     * Revert amount to gift card
     *
     * @param int $id
     * @param int|float $amount
     * @return Enterprise_GiftCardAccount_Model_Observer
     */
    protected function _revertById($id, $amount = 0)
    {
        $giftCard = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->load($id);

        if ($giftCard) {
            $giftCard->revert($amount)
                ->unsOrder()
                ->save();
        }

        return $this;
    }

    protected function _pendingPayment()
    {
        $this->_success(self::BUCKAROO_PENDING_PAYMENT);
    }

    protected function _incorrectPayment($message = '')
    {
        $this->_error($message);
    }

    protected function _verifyError()
    {
        $this->_debugEmail .= "The transaction's authenticity was not verified. \n";
        Mage::getSingleton('core/session')->addNotice(
            Mage::helper('buckaroo3extended')->__('We are currently unable to retrieve the status of your transaction. If you do not receive an e-mail regarding your order within 30 minutes, please contact the shop owner.')
        );

        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/failure_redirect', $this->_order->getStoreId());
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        $this->_debugEmail .= 'Redirecting user to...' . $returnUrl . "\n";

        $this->sendDebugEmail();

        Mage::app()->getResponse()->clearHeaders();
        Mage::app()->getResponse()->setRedirect($returnUrl)->sendResponse();

        return;
    }

    protected function _verifyResponse()
    {
        $verified = false;

        $verifiedSignature = $this->_verifySignature();
        $verifiedDigest = $this->_verifyDigest();

        if ($verifiedSignature === true && $verifiedDigest === true) {
            $verified =  true;
        }

        return $verified;
    }

    protected function _verifySignature()
    {
        $verified = false;

        //save response XML to string
        $responseDomDoc = $this->_responseXML;
        $responseString = $responseDomDoc->saveXML();

        //retrieve the signature value
        $sigatureRegex = "#<SignatureValue>(.*)</SignatureValue>#ims";
        $signatureArray = array();
        preg_match_all($sigatureRegex, $responseString, $signatureArray);

        //decode the signature
        $signature = $signatureArray[1][0];
        $sigDecoded = base64_decode($signature);

        $xPath = new DOMXPath($responseDomDoc);

        //register namespaces to use in xpath query's
        $xPath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
        $xPath->registerNamespace('sig', 'http://www.w3.org/2000/09/xmldsig#');
        $xPath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');

        //Get the SignedInfo nodeset
        $SignedInfoQuery = '//wsse:Security/sig:Signature/sig:SignedInfo';
        $SignedInfoQueryNodeSet = $xPath->query($SignedInfoQuery);
        $SignedInfoNodeSet = $SignedInfoQueryNodeSet->item(0);

        //Canonicalize nodeset
        $signedInfo = $SignedInfoNodeSet->C14N(true, false);

        //get the public key
        $pubKey = openssl_get_publickey(openssl_x509_read(file_get_contents(CERTIFICATE_DIR . DS .'Checkout.pem')));

        //verify the signature
        $sigVerify = openssl_verify($signedInfo, $sigDecoded, $pubKey);

        if ($sigVerify === 1) {
            $verified = true;
        }

        return $verified;
    }

    protected function _verifyDigest()
    {
        $verified = false;

        //save response XML to string
        $responseDomDoc = $this->_responseXML;
        $responseString = $responseDomDoc->saveXML();

        //retrieve the signature value
        $digestRegex = "#<DigestValue>(.*?)</DigestValue>#ims";
        $digestArray = array();
        preg_match_all($digestRegex, $responseString, $digestArray);

        $digestValues = array();
        foreach($digestArray[1] as $digest) {
            $digestValues[] = $digest;
        }

        $xPath = new DOMXPath($responseDomDoc);

        //register namespaces to use in xpath query's
        $xPath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
        $xPath->registerNamespace('sig', 'http://www.w3.org/2000/09/xmldsig#');
        $xPath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');

        $controlHashReference = $xPath->query('//*[@Id="_control"]')->item(0);
        $controlHashCanonical = $controlHashReference->C14N(true, false);
        $controlHash = base64_encode(pack('H*', sha1($controlHashCanonical)));

        $bodyHashReference = $xPath->query('//*[@Id="_body"]')->item(0);
        $bodyHashCanonical = $bodyHashReference->C14N(true, false);
        $bodyHash = base64_encode(pack('H*', sha1($bodyHashCanonical)));

        if (in_array($controlHash, $digestValues) === true && in_array($bodyHash, $digestValues) === true) {
            $verified = true;
        }

        return $verified;
    }

    public function sendNewOrderEmail()
    {
        $currentStore = Mage::app()->getStore()->getId();
        $orderStore = $this->_order->getStoreId();

        Mage::app()->setCurrentStore($orderStore);

        $this->_order->sendNewOrderEmail();

        Mage::app()->setCurrentStore($currentStore);

        return $this;
    }
}
