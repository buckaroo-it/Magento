<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Bancontactmrcash_Observer
    extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code   = 'buckaroo3extended_bancontactmrcash';
    protected $_method = 'bancontactmrcash';

    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $vars = $request->getVars();

        $array = array(
            $this->_method  => array(
                'action'    => 'Pay',
                'version'   => 1,
            ),
        );

        if (Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_' .  $this->_method . '/use_creditmanagement',
            Mage::app()->getStore()->getStoreId()
        )) {
            $array['creditmanagement'] = array(
                    'action'    => 'Invoice',
                    'version'   => 1,
            );
        }

        if (array_key_exists('services', $vars) && is_array($vars['services'])) {
            $vars['services'] = array_merge($vars['services'], $array);
        } else {
            $vars['services'] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    public function buckaroo3extended_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request            = $observer->getRequest();
        $this->_billingInfo = $request->getBillingInfo();
        $this->_order       = $request->getOrder();

        $vars = $request->getVars();

        if (Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_' . $this->_method . '/use_creditmanagement',
            Mage::app()->getStore()->getStoreId()
        )) {
            $this->_addCustomerVariables($vars);
            $this->_addCreditManagement($vars);
            $this->_addAdditionalCreditManagementVariables($vars);
        }

        $array = array();
        if (array_key_exists('customVars', $vars)
            && array_key_exists($this->_method, $vars['customVars'])
            && is_array($vars['customVars'][$this->_method])
        ) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $array);
        } else {
            $vars['customVars'][$this->_method] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    public function buckaroo3extended_request_setmethod(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request  = $observer->getRequest();

        $codeBits = explode('_', $this->_code);
        $code     = end($codeBits);
        $request->setMethod($code);

        return $this;
    }

    protected function _isChosenMethod($observer)
    {
        $ret = false;

        if (null === $observer->getOrder()) {
            return false;
        }

        $chosenMethod = $observer->getOrder()->getPayment()->getMethod();

        if ($chosenMethod === $this->_code) {
            $ret = true;
        }

        return $ret;
    }

    public function buckaroo3extended_refund_request_setmethod(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $codeBits = explode('_', $this->_code);
        $code = end($codeBits);
        $request->setMethod($code);

        return $this;
    }

    public function buckaroo3extended_refund_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $refundRequest = $observer->getRequest();

        $vars = $refundRequest->getVars();

        $array = array(
            'action'    => 'Refund',
            'version'   => 1,
        );

        if (array_key_exists('services', $vars) && is_array($vars['services'][$this->_method])) {
            $vars['services'][$this->_method] = array_merge($vars['services'][$this->_method], $array);
        } else {
            $vars['services'][$this->_method] = $array;
        }

        $refundRequest->setVars($vars);

        return $this;
    }

    /**
     * Add Mr. Cash specific refund variables.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_refund_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        /**
         * Get the creditmemo parameters from the request object.
         */
        $params = Mage::app()->getRequest()->getParam('creditmemo', array());
        if (!isset($params['buckaroo3extended_refund_fields'])
            || !is_array($params['buckaroo3extended_refund_fields'])
        ) {
            return $this;
        }

        /**
         * Get the variables array from the refund request.
         *
         * @var TIG_Buckaroo3Extended_Model_Refund_Request_Abstract $refundRequest
         */
        $refundRequest = $observer->getRequest();
        $vars = $refundRequest->getVars();

        /**
         * Update the refund request with the new variables.
         */
        $refundRequest->setVars($vars);

        return $this;
    }
}
