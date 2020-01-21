<?php
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Empayment_Observer extends Buckaroo_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code   = 'buckaroo3extended_empayment';
    protected $_method = 'empayment';
    protected $_service = 'Empaymentcollecting';

    /**
     * disable this payment method
     * 
     * @param null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return false;
    }

    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $vars = $request->getVars();

        $array = array(
            $this->_service => array(
                'action'    => 'Pay',
                'version'   => 1,
            ),
        );

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_' .  $this->_method . '/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
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
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request            = $observer->getRequest();
        $this->_billingInfo = $request->getBillingInfo();
        $this->_order       = $request->getOrder();

        $vars = $request->getVars();

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_' . $this->_method . '/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
            $this->_addCustomerVariables($vars);
            $this->_addCreditManagement($vars);
            $this->_addAdditionalCreditManagementVariables($vars);
        }

        $this->_addEmpaymentVars($vars);
        $this->_addPersonVars($vars);
        $this->_addBillingAddressVars($vars);
        $request->setVars($vars);
        return $this;
    }

    public function buckaroo3extended_request_setmethod(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $codeBits = explode('_', $this->_code);
        $code = end($codeBits);
        $request->setMethod($code);

        return $this;
    }

    protected function _addEmpaymentVars(&$vars)
    {
        $storeId = Mage::app()->getStore()->getId();

        $array = array(
            'processingtype'           => 'Deferred',
            'reference'                => $this->_order->getIncrementId(),
            'emailAddress'             => $this->_billingInfo['email'],
        );

        if (array_key_exists('customVars', $vars) && array_key_exists($this->_service, $vars['customVars']) && is_array($vars['customVars'][$this->_service])) {
            $vars['customVars'][$this->_service] = array_merge($vars['customVars'][$this->_service], $array);
        } else {
            $vars['customVars'][$this->_service] = $array;
        }
    }

    protected function _addPersonVars(&$vars)
    {
        $array = array(
            'FirstName'     => array(
                                'value' => $this->_billingInfo['firstname'],
                                'group' => 'person',
                            ),
            'Initials'      => array(
                                'value' => $this->_getInitialsCM(),
                                'group' => 'person',
                            ),
            'LastName'      => array(
                                'value' => $this->_billingInfo['lastname'],
                                'group' => 'person',
                            ),
            'browserAgent'  => array(
                                'value' => $_SERVER['HTTP_USER_AGENT'],
                                'group' => 'clientInfo'
                            ),
        );

        if (array_key_exists('customVars', $vars) && array_key_exists($this->_service, $vars['customVars']) && is_array($vars['customVars'][$this->_service])) {
            $vars['customVars'][$this->_service] = array_merge($vars['customVars'][$this->_service], $array);
        } else {
            $vars['customVars'][$this->_service] = $array;
        }
    }

    protected function _addBillingAddressVars(&$vars)
    {
        $address = $this->_processAddressCM();

        $array = array(
            'Street'  => array(
                                'value' => $address['street'],
                                'group' => 'address',
                            ),
            'AddressType'   => array(
                                'value' => 'HOM',
                                'group' => 'address',
                            ),
            'Country'   => array(
                                'value' => 528,
                                'group' => 'address',
                            ),
            'NumberExtension'  => array(
                                'value' => $address['number_addition'],
                                'group' => 'address',
                            ),
            'City'   => array(
                                'value' => $this->_billingInfo['city'],
                                'group' => 'address',
                            ),
            'Number'   => array(
                                'value' => $address['house_number'],
                                'group' => 'address',
                            ),
            'ZipCode'  => array(
                                'value' => $this->_billingInfo['zip'],
                                'group' => 'address',
                            ),
        );

        if (array_key_exists('customVars', $vars) && array_key_exists($this->_service, $vars['customVars']) && is_array($vars['customVars'][$this->_service])) {
            $vars['customVars'][$this->_service] = array_merge($vars['customVars'][$this->_service], $array);
        } else {
            $vars['customVars'][$this->_service] = $array;
        }
    }

    /* deprecated function from v4.7.0*/
    protected function _addBankAccountVars(&$vars)
    {
        $additionalFields = Mage::getSingleton('checkout/session')->getData('additionalFields');

        $array = array(
            'Type'                             => array(
                                                      'value' => 'DOM',
                                                      'group' => 'bankaccount',
                                                  ),
            'DomesticAccountHolderName'        => array(
                                                      'value' => $additionalFields['DOM']['accountHolder'],
                                                      'group' => 'bankaccount',
                                                  ),
            'DomesticCountry'                  => array(
                                                      'value' => 528,
                                                      'group' => 'bankaccount',
                                                  ),
            'DomesticBankIdentifier'           => array(
                                                      'value' => $additionalFields['DOM']['bankId'],
                                                      'group' => 'bankaccount',
                                                  ),
            'DomesticAccountNumber'            => array(
                                                      'value' => $additionalFields['DOM']['accountNumber'],
                                                      'group' => 'bankaccount',
                                                  ),
            'Collect'                          => array(
                                                      'value' => 1,
                                                      'group' => 'bankaccount',
                                                  ),
        );

        if (array_key_exists('customVars', $vars) && array_key_exists($this->_service, $vars['customVars']) && is_array($vars['customVars'][$this->_service])) {
            $vars['customVars'][$this->_service] = array_merge($vars['customVars'][$this->_service], $array);
        } else {
            $vars['customVars'][$this->_service] = $array;
        }
    }

    public function buckaroo3extended_refund_request_setmethod(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
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
        if($this->_isChosenMethod($observer) === false) {
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

    public function buckaroo3extended_refund_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        return $this;
    }
}
