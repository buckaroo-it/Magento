<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Amex_Observer extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code = 'buckaroo3extended_amex';
    protected $_method = 'amex';

    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
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

        if (Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_' .
            $this->_method . '/use_creditmanagement',
            Mage::app()->getStore()->getStoreId()
        )) {
            $array['creditmanagement'] = array(
                'action'  => 'Invoice',
                'version' => 1,
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

        if (Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_' . $this->_method . '/address_verification',
            Mage::app()->getStore()->getStoreId()
        )) {
            $this->_addAavCredentials($vars);
        }

        $request->setVars($vars);

        return $this;
    }

    public function buckaroo3extended_request_setmethod(Varien_Event_Observer $observer)
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

    public function buckaroo3extended_refund_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        return $this;
    }

    /**
     * If AddressVerification is enabled in the config, this method will add the required variables so American Express
     * can validate the address
     * @param $vars
     * @return mixed
     */
    protected function _addAavCredentials(&$vars)
    {
        $billingAddress            = $this->_billingInfo;
        $shippingAddress           = $this->_order->getShippingAddress();

        $billingFirstname          = $billingAddress['firstname'];
        $billingLastname           = $billingAddress['lastname'];
        $billingStreetFull         = $this->_processAddress($billingAddress['address']);
        $billingHousenumber        = $billingStreetFull['house_number'];
        $billingHousenumberSuffix  = $billingStreetFull['number_addition'];
        $billingStreet             = $billingStreetFull['street'];
        $billingZipcode            = $billingAddress['zip'];
        $billingCountry            = $billingAddress['countryCode'];
        $billingPhonenumber        = $this->_processPhoneNumber($billingAddress['telephone']);


        $shippingFirstname            = $shippingAddress->getFirstname();
        $shippingLastname             = $shippingAddress->getLastname();
        $shippingStreetFull        = $this->_processAddress($shippingAddress->getStreetFull());
        $shippingHouseumber        = $shippingStreetFull['house_number'];
        $shippingHousenumberSuffix = $shippingStreetFull['number_addition'];
        $shippingStreet            = $shippingStreetFull['street'];
        $shippingZipcode           = $shippingAddress->getPostcode();
        $shippingPhonenumber       = $this->_processPhoneNumber($shippingAddress->getTelephone());
        $shippingCountryCode        = $shippingAddress->getCountry();

        $customerEmail                = $this->_order->getCustomerEmail();


        $array = array(
            'VerifyAddress'             => 'true',
            'ShippingFirstName'         => $shippingFirstname,
            'ShippingLastName'          => $shippingLastname,
            'ShippingStreet'            => $shippingStreet,
            'ShippingHouseNumber'       => $shippingHouseumber,
            'ShippingHouseNumberSuffix' => $shippingHousenumberSuffix,
            'ShippingPostalCode'        => $shippingZipcode,
            'ShippingCountryCode'       => $shippingCountryCode,
            'ShippingPhoneNumber'       => $shippingPhonenumber['clean'],
            'BillingFirstName'          => $billingFirstname,
            'BillingLastName'           => $billingLastname,
            'BillingStreet'             => $billingStreet,
            'BillingHouseNumber'        => $billingHousenumber,
            'BillingHouseNumberSuffix'  => $billingHousenumberSuffix,
            'BillingPostalCode'         => $billingZipcode,
            'BillingPhoneNumber'        => $billingPhonenumber['clean'],
            'CustomerEmail'             => $customerEmail,
        );

        if (array_key_exists('customVars', $vars)
            && array_key_exists($this->_method, $vars['customVars'])
            && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $array);
        } else {
            $vars['customVars'][$this->_method] = $array;
        }


        return $vars;
    }

    /**
     * @param $telephoneNumber
     * @return array
     */
    protected function _processPhoneNumber($telephoneNumber)
    {
        $number = $telephoneNumber;

        //the final output must like this: 0031123456789 for mobile: 0031612345678
        //so 13 characters max else number is not valid
        //but for some error correction we try to find if there is some faulty notation

        $return = array("orginal" => $number, "clean" => false, "mobile" => false, "valid" => false);
        //first strip out the non-numeric characters:
        $match = preg_replace('/[^0-9]/Uis', '', $number);
        if ($match) {
            $number = $match;
        }

        if (strlen((string)$number) == 13) {
            //if the length equal to 13 is, then we can check if its a mobile number or normal number
            $return['mobile'] = $this->_isMobileNumber($number);
            //now we can almost say that the number is valid
            $return['valid'] = true;
            $return['clean'] = $number;
        } elseif (strlen((string) $number) > 13) {
            //if the number is bigger then 13, it means that there are probably a zero to much
            $return['mobile'] = $this->_isMobileNumber($number);
            $return['clean'] = $this->_isValidNotation($number);
            if (strlen((string)$return['clean']) == 13) {
                $return['valid'] = true;
            }
        } elseif (strlen((string)$number) == 12 or strlen((string)$number) == 11) {
            //if the number is equal to 11 or 12, it means that they used a + in their number instead of 00
            $return['mobile'] = $this->_isMobileNumber($number);
            $return['clean'] = $this->_isValidNotation($number);
            if (strlen((string)$return['clean']) == 13) {
                $return['valid'] = true;
            }
        } elseif (strlen((string)$number) == 10) {
            //this means that the user has no trailing "0031" and therfore only
            $return['mobile'] = $this->_isMobileNumber($number);
            $return['clean'] = '0031'.substr($number, 1);
            if (strlen((string) $return['clean']) == 13) {
                $return['valid'] = true;
            }
        } else {
            //if the length equal to 13 is, then we can check if its a mobile number or normal number
            $return['mobile'] = $this->_isMobileNumber($number);
            //now we can almost say that the number is valid
            $return['valid'] = true;
            $return['clean'] = $number;
        }

        return $return;
    }
}
