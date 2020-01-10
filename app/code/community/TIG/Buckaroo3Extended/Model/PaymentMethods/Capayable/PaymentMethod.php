<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod
    extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_formBlockType = 'buckaroo3extended/paymentMethods_capayable_checkout_form';

    protected $_orderMailStatusses = array(
        TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS,
        TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_PENDING_PAYMENT
    );

    /**
     * @param null|Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        // Check if quote is null, and try to look it up based on adminhtml session
        if (!$quote && Mage::helper('buckaroo3extended')->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote');
        }

        if ($quote && $this->isShippingDifferent($quote)) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Checks if shipping address is different from billing address
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    protected function isShippingDifferent($quote)
    {
        //get both the order-addresses
        $billingAddress = $this->getAddressData($quote->getBillingAddress());
        $shippingAddress = $this->getAddressData($quote->getShippingAddress());

        //differentiate the addresses, when some data is different an array with changes will be returned
        $addressDiff = array_diff($billingAddress, $shippingAddress);

        if (!empty($addressDiff)) {
            return true;
        }

        return false;
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return array
     */
    protected function getAddressData($address)
    {
        $data = array(
            'firstname'  => $address->getFirstname(),
            'lastname'   => $address->getLastname(),
            'name'       => $address->getName(),
            'street'     => implode(" ", $address->getStreet()),
            'street1'    => $address->getStreet1(),
            'streetfull' => $address->getStreetFull(),
            'postcode'   => $address->getPostcode(),
            'city'       => $address->getCity(),
            'country'    => $address->getCountry(),
            'phone'      => $address->getTelephone(),
            'email'      => $address->getEmail(),
        );

        if ($address->getStreet2()) {
            $data['street2'] = $address->getStreet2();
        }

        if ($address->getFax()) {
            $data['fax'] = $address->getFax();
        }

        if ($address->getCompany()) {
            $data['company'] = $address->getCompany();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderPlaceRedirectUrl()
    {
        $array = $this->getBPEPostData();

        $session = Mage::getSingleton('checkout/session');
        $session->setData('additionalFields', $array);

        return parent::getOrderPlaceRedirectUrl();
    }

    /**
     * @return array
     */
    protected function getBPEPostData()
    {
        $post = Mage::app()->getRequest()->getPost();

        $customerBirthDate = $this->getBirthdate($post['payment'][$this->_code]);

        $array = array(
            'BPE_Customergender'    => $post[$this->_code . '_BPE_Customergender'],
            'BPE_Customerbirthdate' => $customerBirthDate,
            'BPE_OrderAs'           => (int)$post[$this->_code . '_BPE_OrderAs'],
        );

        if ((int)$array['BPE_OrderAs'] != 1) {
            $array['BPE_CompanyCOCRegistration'] = $post[$this->_code . '_BPE_CompanyCOCRegistration'];
            $array['BPE_CompanyName'] = $post[$this->_code . '_BPE_CompanyName'];
        }

        return $array;
    }

    /**
     * @param array $birthdateData
     *
     * @return false|string
     */
    protected function getBirthdate($birthdateData)
    {
        $customerBirthDate = date(
            'Y-m-d',
            strtotime($birthdateData['year'] . '-' . $birthdateData['month'] . '-' . $birthdateData['day'])
        );

        return $customerBirthDate;
    }

    /**
     * @param $responseData
     *
     * @return string
     */
    // @codingStandardsIgnoreLine
    public function getRejectedMessage($responseData)
    {
        $rejectedMessage = "Your request to pay with Capayable has been rejected. "
            . "For questions about the rejection, please contact Capayable Customer Service: "
            . "<a href='https://www.capayable.com/klantenservice' target='_blank'>"
            . "https://www.capayable.com/klantenservice</a>. You can also choose to select a different payment method.";

        return $rejectedMessage;
    }
}
