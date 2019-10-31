<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Transfer_Observer extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code = 'buckaroo3extended_transfer';
    protected $_method = 'transfer';

    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $vars = $request->getVars();

        $array = array(
            $this->_method     => array(
                'action'    => 'Pay',
                'version'   => 1,
            ),
        );

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_transfer/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
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

        $this->_addTransfer($vars);

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_transfer/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
            $this->_addCreditManagement($vars);
            $this->_addAdditionalCreditManagementVariables($vars);
            $this->_addCustomerVariables($vars, 'creditmanagement');

            if (!isset($vars['customVars']['creditmanagement']['PhoneNumber'])) {
                $vars['customVars']['creditmanagement']['PhoneNumber'] = $vars['customVars']['creditmanagement']['MobilePhoneNumber'];
            }
        }

        $request->setVars($vars);

        return $this;
    }

    protected function _addTransfer(&$vars)
    {
        $dueDays = Mage::getStoreConfig('buckaroo/buckaroo3extended_transfer/due_date', Mage::app()->getStore()->getStoreId());
        $dueDate = date('Y-m-d', mktime(0, 0, 0, date("m"), (date("d") + $dueDays), date("Y")));

        $array = array(
            'SendMail'          => Mage::getStoreConfig('buckaroo/buckaroo3extended_transfer/send_mail', Mage::app()->getStore()->getStoreId()) ? 'true' : 'false',
            'customeremail'     => $this->_billingInfo['email'],
            'customercountry'   => $this->_billingInfo['countryCode'],
            'customergender'    => '0',
            'customerFirstName' => $this->_billingInfo['firstname'],
            'customerLastName'  => $this->_billingInfo['lastname'],
            'DateDue'           => $dueDate,
        );
        if (array_key_exists('customVars', $vars) && array_key_exists('transfer', $vars['customVars']) && is_array($vars['customVars']['transfer'])) {
            $vars['customVars']['transfer'] = array_merge($vars['customVars']['transfer'], $array);
        } else {
            $vars['customVars']['transfer'] = $array;
        }
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
