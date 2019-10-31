<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Paypal_Observer extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code = 'buckaroo3extended_paypal';
    protected $_method = 'paypal';

    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();
        $order = $request->getOrder();
        $vars = $request->getVars();

        $array = array(
            $this->_method => array(
                'name' => 'paypal',
                'action' => 'pay',
                'version' => 1,
            ),
        );

        $checkForSellerProtection = Mage::helper('buckaroo3extended')->checkSellersProtection($order);
        
        $this->_addCommentHistoryForVirtual($order);

        if ($checkForSellerProtection){
            $array['sellersprotection'] = array(
                    'name' => 'paypal',
                    'action' => 'extraInfo',
                    'version' => 1,
           );
        }

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
        $this->_order       = $request->getOrder();
        $shippingAddress    = $this->_order->getShippingAddress();

        $arrayCustom        = $this->_getSellerProtectionVars($this->_order, $shippingAddress);
        $vars               = $request->getVars();
        
        if ($arrayCustom) {
            if (array_key_exists('customVars', $vars) && array_key_exists('sellersprotection', $vars['customVars']) && is_array($vars['customVars']['sellersprotection'])) {
                $vars['customVars']['sellersprotection'] = array_merge($vars['customVars']['sellersprotection'], $arrayCustom);
            } else {
                $vars['customVars']['sellersprotection'] = $arrayCustom;
            }
        }

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_' . $this->_method . '/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
            $this->_addCustomerVariables($vars);
            $this->_addCreditManagement($vars);
            $this->_addAdditionalCreditManagementVariables($vars);
        }

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

    public function buckaroo3extended_push_custom_processing_after(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $order = $observer->getOrder();
        $push = $observer->getPush()->getPostArray();
        $response = $observer->getResponse();

        if($response['status'] !== self::BUCKAROO_SUCCESS) {
            return $this;
        }

        if(!isset($push['brq_SERVICE_paypal_ProtectionEligibility']) &&
            !isset($push['brq_service_paypal_ProtectionEligibility'])) {
                return $this;
        }

        if(!isset($push['brq_SERVICE_paypal_ProtectionEligibility'])){
            $eligibility = $push['brq_service_paypal_ProtectionEligibility'];
        } else {
            $eligibility = $push['brq_SERVICE_paypal_ProtectionEligibility'];
        }

        if(!isset($push['brq_SERVICE_paypal_ProtectionEligibilityType'])){
            $eligibilityType = $push['brq_service_paypal_ProtectionEligibilityType'];
        } else {
            $eligibilityType = $push['brq_SERVICE_paypal_ProtectionEligibilityType'];
        }

        if ($eligibility == 'Ineligible') {
            $eligibilityType = 'None';
        }

        $commentEligible = Mage::helper('buckaroo3extended')->__(
            'Merchant is protected by PayPals Seller Protection Policy for both Unauthorized Payment and Item Not Received'
        );
        $commentItemNotReceivedEligible = Mage::helper('buckaroo3extended')->__(
            'Merchant is protected by Paypals Seller Protection Policy for Item Not Received'
        );
        $commentUnauthorizedPaymentEligible = Mage::helper('buckaroo3extended')->__(
            'Merchant is protected by Paypals Seller Protection Policy for Unauthorized Payment'
        );
        $commentIneligible = Mage::helper('buckaroo3extended')->__(
            'Merchant is not protected under the Seller Protection Policy'
        );

        switch ($eligibilityType) {
            case 'Eligible':
                $eligibilityStatus = Mage::getStoreConfig(
                    'buckaroo/buckaroo3extended_paypal/sellers_protection_eligible',
                    $order->getStoreId()
                );
                $order->addStatusHistoryComment($commentEligible, $eligibilityStatus)
                      ->save();
                break;

            case 'ItemNotReceivedEligible':
                $eligibilityStatus = Mage::getStoreConfig(
                    'buckaroo/buckaroo3extended_paypal/sellers_protection_itemnotreceived_eligible',
                    $order->getStoreId()
                );
                $order->addStatusHistoryComment($commentItemNotReceivedEligible, $eligibilityStatus)
                      ->save();
                break;

            case 'UnauthorizedPaymentEligible':
                $eligibilityStatus = Mage::getStoreConfig(
                    'buckaroo/buckaroo3extended_paypal/sellers_protection_unauthorizedpayment_eligible',
                    $order->getStoreId()
                );
                $order->addStatusHistoryComment($commentUnauthorizedPaymentEligible, $eligibilityStatus)
                      ->save();
                break;

            case 'None':
                $eligibilityStatus = Mage::getStoreConfig(
                    'buckaroo/buckaroo3extended_paypal/sellers_protection_ineligible',
                    $order->getStoreId()
                );
                $order->addStatusHistoryComment($commentIneligible, $eligibilityStatus)
                      ->save();
                break;
        }

        return $this;
    }
}
