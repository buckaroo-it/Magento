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
class TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Observer
    extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_method = 'Capayable';

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request            = $observer->getRequest();
        $this->_billingInfo = $request->getBillingInfo();
        $this->_order       = $request->getOrder();

        $vars = $request->getVars();

        $this->addCustomerData($vars);
        $this->addProductData($vars);
        $this->addSubtotalData($vars);

        $request->setVars($vars);

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
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

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_refund_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $refundRequest = $observer->getRequest();
        $vars = $refundRequest->getVars();

        $array = array(
            'action'    => 'Refund',
            'version'   => $this->_getServiceVersion(),

        );

        if ($this->_method == false){
            $storeId = Mage::app()->getStore()->getStoreId();
            $this->_method = Mage::getStoreConfig('buckaroo/' . $this->_code . '/paymethod', $storeId);
        }

        if (array_key_exists('services', $vars) && is_array($vars['services'][$this->_method])) {
            $vars['services'][$this->_method] = array_merge($vars['services'][$this->_method], $array);
        } else {
            $vars['services'][$this->_method] = $array;
        }

        $refundRequest->setVars($vars);

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_refund_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        /** @var TIG_Buckaroo3Extended_Model_Request_Abstract $request */
        $request = $observer->getRequest();
        $vars = $request->getVars();

        $vars['channel'] = 'Web';

        $request->setVars($vars);

        return $this;
    }

    /**
     * @param $vars
     */
    protected function addCustomerData(&$vars)
    {
        $country = $this->_billingInfo['countryCode'];
        $phoneNumber = ($country == 'BE' ? $this->_processPhoneNumberCMBe() : $this->_processPhoneNumberCM());

        $array = array(
            'CustomerType' => $this->getCustomerType(),
            'InvoiceDate' => date('d-m-Y'),
            'Phone' => array(
                'value' => $phoneNumber['clean'],
                'group' => 'Phone'
            ),
            'Email' => array(
                'value' => $this->_billingInfo['email'],
                'group' => 'Email'
            )
        );

        $array = array_merge($array, $this->getPersonGroupData());
        $array = array_merge($array, $this->getAddressGroupData());
        $array = array_merge($array, $this->getCompanyGroupData());

        if (array_key_exists('customVars', $vars)
            && array_key_exists($this->_method, $vars['customVars'])
            && is_array($vars['customVars'][$this->_method])
        ) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $array);
        } else {
            $vars['customVars'][$this->_method] = $array;
        }
    }

    /**
     * @return array
     */
    protected function getPersonGroupData()
    {
        $additionalFields = Mage::getSingleton('checkout/session')->getData('additionalFields');
        $gender = 0;
        $birthdate = '';

        if (Mage::helper('buckaroo3extended')->isAdmin()) {
            $additionalFields = Mage::getSingleton('core/session')->getData('additionalFields');
        }

        if (isset($additionalFields['BPE_Customergender'])) {
            $gender = $additionalFields['BPE_Customergender'];
        }

        if (isset($additionalFields['BPE_Customerbirthdate'])) {
            $birthdate = $additionalFields['BPE_Customerbirthdate'];
        }

        $array = array(
            'Initials' => array(
                'value' => $this->_getInitialsCM(),
                'group' => 'Person'
            ),
            'LastName' => array(
                'value' => $this->_billingInfo['lastname'],
                'group' => 'Person'
            ),
            'Culture' => array(
                'value' => 'nl-NL',
                'group' => 'Person'
            ),
            'Gender' => array(
                'value' => $gender,
                'group' => 'Person'
            ),
            'BirthDate' => array(
                'value' => $birthdate,
                'group' => 'Person'
            )
        );

        return $array;
    }

    /**
     * @return array
     */
    protected function getAddressGroupData()
    {
        $address = $this->_processAddressCM();
        $country = $this->_billingInfo['countryCode'];

        $array = array(
            'Street' => array(
                'value' => $address['street'],
                'group' => 'Address'
            ),
            'HouseNumber' => array(
                'value' => $address['house_number'],
                'group' => 'Address'
            ),
            'HouseNumberSuffix' => array(
                'value' => $address['number_addition'],
                'group' => 'Address'
            ),
            'ZipCode' => array(
                'value' => $this->_billingInfo['zip'],
                'group' => 'Address'
            ),
            'City' => array(
                'value' => $this->_billingInfo['city'],
                'group' => 'Address'
            ),
            'Country' => array(
                'value' => $country,
                'group' => 'Address'
            )
        );

        return $array;
    }

    /**
     * @return array
     */
    protected function getCompanyGroupData()
    {
        $additionalFields = Mage::getSingleton('checkout/session')->getData('additionalFields');

        if (Mage::helper('buckaroo3extended')->isAdmin()) {
            $additionalFields = Mage::getSingleton('core/session')->getData('additionalFields');
        }

        if (!isset($additionalFields['BPE_OrderAs']) || $additionalFields['BPE_OrderAs'] == 1) {
            return array();
        }

        $array = array(
            'Name' => array(
                'value' => $additionalFields['BPE_CompanyName'],
                'group' => 'Company'
            ),
            'ChamberOfCommerce' => array(
                'value' => $additionalFields['BPE_CompanyCOCRegistration'],
                'group' => 'Company'
            )
        );

        return $array;
    }

    /**
     * @return string
     */
    protected function getCustomerType()
    {
        $customerType = '';
        $additionalFields = Mage::getSingleton('checkout/session')->getData('additionalFields');

        if (Mage::helper('buckaroo3extended')->isAdmin()) {
            $additionalFields = Mage::getSingleton('core/session')->getData('additionalFields');
        }

        if (!isset($additionalFields['BPE_OrderAs']) || empty($additionalFields['BPE_OrderAs'])) {
            return $customerType;
        }

        switch ($additionalFields['BPE_OrderAs']) {
            case 1:
                $customerType = 'Debtor';
                break;
            case 2:
                $customerType = 'Company';
                break;
            case 3:
                $customerType = 'SoleProprietor';
                break;
        }

        return $customerType;
    }

    /**
     * @param array $vars
     */
    protected function addProductData(&$vars)
    {
        $products = $this->_order->getAllItems();
        $max      = 99;
        $i        = 1;
        $group    = array();

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($products as $item) {
            if (empty($item) || $item->hasParentItemId()) {
                continue;
            }

            $group[] = $this->getProductArticle($item, $i++);

            if ($i > $max) {
                break;
            }
        }

        $group = array_merge($group, $this->getGiftwrapArticles($i));

        $requestArray = array('Articles' => $group);

        if (array_key_exists('customVars', $vars)
            && array_key_exists($this->_method, $vars['customVars'])
            && is_array($vars['customVars'][$this->_method])
        ) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $requestArray);
        } else {
            $vars['customVars'][$this->_method] = $requestArray;
        }
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     * @param int                         $groupId
     *
     * @return array
     */
    protected function getProductArticle($item, $groupId = 1)
    {
        $article = array();

        $article['Code']['value']       = $item->getSku();
        $article['Code']['group']       = 'ProductLine';
        $article['Code']['groupId']     = $groupId;
        $article['Name']['value']       = $item->getName();
        $article['Name']['group']       = 'ProductLine';
        $article['Name']['groupId']     = $groupId;
        $article['Quantity']['value']   = round($item->getQtyOrdered(), 0);
        $article['Quantity']['group']   = 'ProductLine';
        $article['Quantity']['groupId'] = $groupId;
        $article['Price']['value']      = $item->getBasePriceInclTax();
        $article['Price']['group']      = 'ProductLine';
        $article['Price']['groupId']    = $groupId;

        return $article;
    }

    /**
     * @param int $groupId
     *
     * @return array
     */
    protected function getGiftwrapArticles($groupId = 1)
    {
        if (!Mage::helper('buckaroo3extended')->isEnterprise()) {
            return array();
        }

        $gwId = 1;
        $gwGroup = array();

        if ($this->_order->getGwBasePrice() > 0) {
            $gwPrice = $this->_order->getGwBasePrice() + $this->_order->getGwBaseTaxAmount();

            /** @var Mage_Sales_Model_Order_Item $gwArticleModel */
            $gwArticleModel = Mage::getModel('sales/order_item');
            $gwArticleModel->setSku('gwo_' . $this->_order->getGwId());
            $gwArticleModel->setName(Mage::helper('buckaroo3extended')->__('Gift Wrapping for Order'));
            $gwArticleModel->setQtyOrdered(1);
            $gwArticleModel->setBasePriceInclTax($gwPrice);

            $gwArticle = $this->getProductArticle($gwArticleModel, $groupId++);
            $gwGroup[] = $gwArticle;

            $gwId += $this->_order->getGwId();
        }

        if ($this->_order->getGwItemsBasePrice() > 0) {
            $gwiPrice = $this->_order->getGwItemsBasePrice() + $this->_order->getGwItemsBaseTaxAmount();

            /** @var Mage_Sales_Model_Order_Item $gwiArticleModel */
            $gwiArticleModel = Mage::getModel('sales/order_item');
            $gwiArticleModel->setSku('gwi_' . $gwId);
            $gwiArticleModel->setName(Mage::helper('buckaroo3extended')->__('Gift Wrapping for Items'));
            $gwiArticleModel->setQtyOrdered(1);
            $gwiArticleModel->setBasePriceInclTax($gwiPrice);

            $gwiArticle = $this->getProductArticle($gwiArticleModel, $groupId);
            $gwGroup[] = $gwiArticle;
        }

        return $gwGroup;
    }

    /**
     * @param $vars
     */
    protected function addSubtotalData(&$vars)
    {
        if (!isset($vars['customVars'][$this->_method]['Articles'])
            || empty($vars['customVars'][$this->_method]['Articles'])
        ) {
            $vars['customVars'][$this->_method]['Articles'] = array();
        }

        $groupId = 1;
        $vars['customVars'][$this->_method]['Articles'][] = $this->getDiscountLine($groupId);
        $vars['customVars'][$this->_method]['Articles'][] = $this->getFeeLine($groupId);
        $vars['customVars'][$this->_method]['Articles'][] = $this->getShippingCostsLine($groupId);
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param int    $groupId
     *
     * @return array
     */
    protected function getSubtotalLine($name, $value, $groupId = 1)
    {
        $subtotalLine = array();

        $subtotalLine['Name']['value']    = $name;
        $subtotalLine['Name']['group']    = 'SubtotalLine';
        $subtotalLine['Name']['groupId']  = $groupId;
        $subtotalLine['Value']['value']   = $value;
        $subtotalLine['Value']['group']   = 'SubtotalLine';
        $subtotalLine['Value']['groupId'] = $groupId;

        return $subtotalLine;
    }

    /**
     * @param int $groupId
     *
     * @return array
     */
    protected function getDiscountLine(&$groupId)
    {
        $discount = abs((double)$this->_order->getDiscountAmount());

        if (Mage::helper('buckaroo3extended')->isEnterprise() && (double)$this->_order->getGiftCardsAmount() > 0) {
            $discount += (double)$this->_order->getGiftCardsAmount();
        }

        if ($discount <= 0) {
            return array();
        }

        $discount = (-1 * round($discount, 2));
        $discountLine = $this->getSubtotalLine('Korting', $discount, $groupId++);

        return $discountLine;
    }

    /**
     * @param int $groupId
     *
     * @return array
     */
    protected function getFeeLine(&$groupId)
    {
        $fee    = (double) $this->_order->getBuckarooFee();

        if ($fee <= 0) {
            return array();
        }

        $feeTax = (double) $this->_order->getBuckarooFeeTax();
        $feeInclTax = round($fee + $feeTax, 2);

        $feeLine = $this->getSubtotalLine('Betaaltoeslag', $feeInclTax, $groupId++);

        return $feeLine;
    }

    /**
     * @param int $groupId
     *
     * @return array
     */
    protected function getShippingCostsLine(&$groupId)
    {
        $shippingCosts = $this->_order->getBaseShippingInclTax();

        if ($shippingCosts <= 0) {
            return array();
        }

        $shippingCosts = round($shippingCosts, 2);

        $shippingCostsLine = $this->getSubtotalLine('Verzendkosten', $shippingCosts, $groupId++);

        return $shippingCostsLine;
    }
}
