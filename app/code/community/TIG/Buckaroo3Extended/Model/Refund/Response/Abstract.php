<?php
class TIG_Buckaroo3Extended_Model_Refund_Response_Abstract extends TIG_Buckaroo3Extended_Model_Response_Abstract
{
    protected $_payment;

    public function setPayment($payment)
    {
        $this->_payment = $payment;
    }

    public function getPayment()
    {
        return $this->_payment;
    }

    public function __construct($data)
    {
        $this->setOrder($data['order']);
        $this->setPayment($data['payment']);
        parent::__construct($data);
    }

    public function processResponse()
    {
        if ($this->_response === false) {
            $this->_debugEmail .= "An error occurred.";
            $this->_error();
        }

        $this->_debugEmail .= "Verifying authenticity of the response...";
        $verified = $this->_verifyResponse();

        if ($verified !== true) {
            $this->_debugEmail .= "Authenticity could NOT be verified!";
            $this->_verifyError();
        }

        $this->_debugEmail .= "Verified as authentic! \n\n";

        $this->_payment->setTransactionKey($this->_response->Key)->save();

        $parsedResponse = $this->_parseResponse();
        $this->_debugEmail .= "Parsed response: " . var_export($parsedResponse, true) . "\n";

        $this->_debugEmail .= "Dispatching custom order processing event... \n";

        Mage::dispatchEvent(
            'buckaroo3extended_refund_response_custom_processing',
            array(
                'model' => $this,
                'order'         => $this->getOrder(),
                'response'      => $parsedResponse,
            )
        );

        $this->_requiredAction($parsedResponse);
        return $this;
    }


    protected function _success($status = self::BUCKAROO_SUCCESS)
    {
        $this->_debugEmail .= 'The refund request has been accepted \n';

        $this->sendDebugEmail();

        return $this;
    }

    protected function _rejected($message = '')
    {
        $this->_debugEmail .= 'The refund request has been rejected \n';

        $this->_updateRefundedOrderStatus(false);

        $this->sendDebugEmail();

        Mage::throwException(Mage::helper('buckaroo3extended')->__($this->_response->Status->Code->_));

        return $this;
    }

    protected function _failed($message = '')
    {
        $this->_debugEmail .= 'The transaction request has failed. \n';

        $this->_updateRefundedOrderStatus(false);

        $this->sendDebugEmail();

        Mage::throwException(Mage::helper('buckaroo3extended')->__($this->_response->Status->Code->_));
    }

    protected function _error($message = '')
    {
        $this->_debugEmail .= 'The transaction request produced an error. \n';

        if ($this->getOrder()) {
            $this->_updateRefundedOrderStatus(false);
        }

        $this->sendDebugEmail();

        if (isset($this->_response) && isset($this->_response->Status->Code->_)) {
            Mage::throwException(Mage::helper('buckaroo3extended')->__('Buckaroo returned the following error: %s', $this->_response->Status->Code->_));
        } else {
            Mage::throwException('An unknown error occurred.');
        }

    }

    protected function _neutral()
    {
        $this->_failed();
    }

    protected function _verifyError()
    {
        $this->_failed();
    }

    protected function _pendingPayment()
    {
        $this->_debugEmail .= 'This refund request has been put on hold. \n';

        $this->_updateRefundedOrderStatus(false);

        $this->sendDebugEmail();

        Mage::throwException(Mage::helper('buckaroo3extended')->__("This refund request has been put on hold by Buckaroo. You can find out details regarding the action and complete the refund in Buckaroo Payment Plaza."));
    }
}
