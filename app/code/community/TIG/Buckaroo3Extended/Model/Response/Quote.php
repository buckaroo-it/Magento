<?php
class TIG_Buckaroo3Extended_Model_Response_Quote extends TIG_Buckaroo3Extended_Model_Response_Abstract
{
    public function __construct($data)
    {
        // get quote from session
        $session = Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();

        // use quote as order
        $this->setOrder($quote);
        $this->setMethod($quote->getPayment()->getMethodCode());

        parent::__construct($data);
    }

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

        if (isset($this->_response->Key))
        {
            Mage::getSingleton('checkout/session')->setBuckarooMasterPassTrx($this->_response->Key);
            $this->_debugEmail .= 'Transaction key saved in session: ' . $this->_response->Key . "\n";
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

        return $this->_requiredAction($parsedResponse);
    }

    protected function _success($status = self::BUCKAROO_SUCCESS)
    {
        $this->sendDebugEmail();

        // this will never happen, since we are working with a quote
        Mage::throwException('An error occurred while processing the request');
    }

    protected function _failed($message = '')
    {
        $this->_debugEmail .= 'The transaction was unsuccessful. \n';
        $this->_debugEmail .= "Returning response parameters.\n";
        $this->sendDebugEmail();

        return array(
            'error' => Mage::helper('buckaroo3extended')->__('Your payment was unsuccessful. Please try again or choose another payment method.'),
        );
    }

    protected function _error($message = '')
    {
        $this->_debugEmail .= "The transaction generated an error. \n";
        $this->_debugEmail .= "Returning response parameters.\n";
        $this->sendDebugEmail();

        return array(
            'error' => Mage::helper('buckaroo3extended')->__('A technical error has occurred. Please try again. If this problem persists, please contact the shop owner.'),
        );
    }

    protected function _rejected($message = '')
    {
        $this->_debugEmail .= "The transaction generated an error. \n";
        $this->_debugEmail .= "Returning response parameters.\n";
        $this->sendDebugEmail();

        return array(
            'error' => Mage::helper('buckaroo3extended')->__('The payment has been rejected, please try again or select a different paymentmethod.'),
        );
    }

    protected function _neutral()
    {
        $this->_debugEmail .= "The response is neutral (not successful, not unsuccessful). \n";
        $this->_debugEmail .= "Returning response parameters.\n";
        $this->sendDebugEmail();

        $parameters = array();
        $responseParameters = $this->getResponse();

        if(isset($responseParameters->Services->Service->ResponseParameter))
        {
            foreach($responseParameters->Services->Service->ResponseParameter as $responseParameter)
            {
                $parameters[lcfirst($responseParameter->Name)] = $responseParameter->_;
            }
        }

        return $parameters;
    }

    protected function _verifyError()
    {
        $this->_debugEmail .= "The transaction's authenticity was not verified. \n";
        $this->_debugEmail .= "Returning response parameters.\n";
        $this->sendDebugEmail();

        return array(
            'error' => Mage::helper('buckaroo3extended')->__('We are currently unable to retrieve the status of your transaction. If you do not receive an e-mail regarding your order within 30 minutes, please contact the shop owner.'),
        );
    }
}
