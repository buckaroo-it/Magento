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
class TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_Observer
    extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code = 'buckaroo3extended_afterpay20';
    protected $_method = 'afterpay';

    /** @var  TIG_Buckaroo3Extended_Helper_Data */
    protected $helper;

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();
        $vars = $request->getVars();

        $array = array(
            $this->_method => array(
                'action'   => 'Pay',
                'version'  => '1',
            ),
        );

        if (array_key_exists('services', $vars) && is_array($vars['services'])) {
            $vars['services'] = array_merge($vars['services'], $array);
        } else {
            $vars['services'] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

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

        $this->addAfterpayVariables($vars);
        $this->addArticlesVariables($vars);

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

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     * @throws Mage_Core_Model_Store_Exception
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
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_refund_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();
        $this->_order = $request->getOrder();
        $payment     = $request->getPayment();

        $vars = $request->getVars();

        $this->addCreditmemoArticlesVariables($vars, $payment);

        $request->setVars($vars);

        return $this;
    }

    /**
     * @return Mage_Core_Helper_Abstract|TIG_Buckaroo3Extended_Helper_Data
     */
    private function getHelper()
    {
        if ($this->helper == null) {
            $this->helper = Mage::helper('buckaroo3extended');
        }

        return $this->helper;
    }

    /**
     * @param array $vars
     */
    private function addAfterpayVariables(&$vars)
    {
        $session            = Mage::getSingleton('checkout/session');
        $additionalFields   = $session->getData('additionalFields');
        $paymentAdditionalInformation = $this->_order->getPayment()->getAdditionalInformation();

        if (is_array($paymentAdditionalInformation) && !empty($paymentAdditionalInformation)) {
            $additionalFields = $paymentAdditionalInformation;
        }

        $requestArray = array();

        //add billing address
        $billingInfo = $this->getAddressData($this->_order->getBillingAddress(), $additionalFields, 'BillingCustomer');
        $requestArray = array_merge($requestArray, $billingInfo);

        // Compatible with postnl pakjegemak
        $pakjeGemakAddress = $this->getPakjeGemakAddress();

        //add shipping address (only when different from billing address)
        if ($this->isShippingDifferent() || $pakjeGemakAddress) {
            $shippingAddress = $this->getShippingAddress($pakjeGemakAddress);
            $shippingInfo = $this->getAddressData($shippingAddress, $additionalFields, 'ShippingCustomer');

            $requestArray = array_merge($requestArray, $shippingInfo);
        }

        if (array_key_exists('customVars', $vars) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $requestArray);
        } else {
            $vars['customVars'][$this->_method] = $requestArray;
        }
    }

    /**
     * @param Mage_Sales_Model_Order_Address $address
     * @param array                          $additionalFields
     * @param string                         $addressType
     *
     * @return array
     */
    private function getAddressData($address, $additionalFields, $addressType = 'BillingCustomer')
    {
        $streetFull         = $this->_processAddress($address->getStreetFull());
        $rawPhoneNumber     = $address->getTelephone();

        if (!is_numeric($rawPhoneNumber) || $rawPhoneNumber == '-') {
            $rawPhoneNumber = $additionalFields['BPE_PhoneNumber'];
        }

        $phonenumber = ($address->getCountryId() == 'BE'
            ? $this->_processPhoneNumberCMBe($rawPhoneNumber) : $this->_processPhoneNumberCM($rawPhoneNumber));
        $phonenumberKey = ($phonenumber['mobile'] ? 'MobilePhone' : 'Phone');

        $addressInfo = array();
        $addressInfo[] = $this->getParameterLine('Category', 'Person', $addressType);
        $addressInfo[] = $this->getParameterLine('Salutation', $additionalFields['BPE_Customergender'], $addressType);
        $addressInfo[] = $this->getParameterLine('FirstName', $address->getFirstname(), $addressType);
        $addressInfo[] = $this->getParameterLine('LastName', $address->getLastname(), $addressType);
        $addressInfo[] = $this->getParameterLine('BirthDate', $additionalFields['BPE_customerbirthdate'], $addressType);
        $addressInfo[] = $this->getParameterLine('Street', $streetFull['street'], $addressType);
        $addressInfo[] = $this->getParameterLine('StreetNumber', $streetFull['house_number'], $addressType);
        $addressInfo[] = $this->getParameterLine('StreetNumberAdditional', $streetFull['number_addition'], $addressType);
        $addressInfo[] = $this->getParameterLine('PostalCode', $address->getPostcode(), $addressType);
        $addressInfo[] = $this->getParameterLine('City', $address->getCity(), $addressType);
        $addressInfo[] = $this->getParameterLine('Country', $address->getCountryId(), $addressType);
        $addressInfo[] = $this->getParameterLine($phonenumberKey, $phonenumber['clean'], $addressType);
        $addressInfo[] = $this->getParameterLine('Email', $address->getEmail(), $addressType);


        //TODO: Enable when finnish id number is implemented
        if ($additionalFields['identification_number']) {
            $addressInfo[] = $this->getParameterLine('IdentificationNumber', $additionalFields['identification_number'], $addressType);
        }

        if ($this->_order->getCustomerId()) {
            $addressInfo[] = $this->getParameterLine('CustomerNumber', $this->_order->getCustomerId(), $addressType);
        }

        return $addressInfo;
    }

    /**
     * @param bool|Mage_Sales_Model_Order_Address $pakjeGemakAddress
     *
     * @return Mage_Sales_Model_Order_Address
     */
    private function getShippingAddress($pakjeGemakAddress = false)
    {
        $shippingAddress = $this->_order->getShippingAddress();

        if (!$shippingAddress) {
            $shippingAddress = $this->_order->getBillingAddress();
        }

        if (!$pakjeGemakAddress) {
            return $shippingAddress;
        }

        //Update with pakjeGemak values
        $shippingAddress->setFirstname('A');
        $shippingAddress->setLastname('POSTNL afhaalpunt ' . $pakjeGemakAddress->getCompany());
        $shippingAddress->setStreetFull($pakjeGemakAddress->getStreetFull());
        $shippingAddress->setPostcode($pakjeGemakAddress->getPostcode());
        $shippingAddress->setCity($pakjeGemakAddress->getCity());
        $shippingAddress->setCountryId($pakjeGemakAddress->getCountryId());
        $shippingAddress->setTelephone($pakjeGemakAddress->getTelephone());

        return $shippingAddress;
    }

    /**
     * @return bool|Mage_Sales_Model_Order_Address
     */
    private function getPakjeGemakAddress()
    {
        $pakjeGemakAddress = false;

        if (!Mage::helper('core')->isModuleEnabled('TIG_PostNL')) {
            return $pakjeGemakAddress;
        }

        $addresses = $this->_order->getAddressesCollection();

        foreach ($addresses as $addressNew) {
            if ($addressNew->getAddressType() == 'pakje_gemak') {
                $pakjeGemakAddress = $addressNew;
                break;
            }
        }

        return $pakjeGemakAddress;
    }

    /**
     * @param string      $name
     * @param string      $value
     * @param string|null $group
     * @param int|null    $groupId
     *
     * @return array
     */
    private function getParameterLine($name, $value, $group = null, $groupId = null)
    {
        $parameterContent = array(
            'name' => $name,
            'value' => $value
        );

        if (strlen($group) > 0) {
            $parameterContent['group'] = $group;
        }

        if ($groupId > 0) {
            $parameterContent['groupId'] = $groupId;
        }

        $parameter = $parameterContent;

        return $parameter;
    }

    /**
     * @param Mage_Sales_Model_Order|Mage_Sales_Model_Order_Invoice $discountObject
     *
     * @return float|int
     */
    protected function calculateDiscount($discountObject)
    {
        $discount = 0;
        $helper = $this->getHelper();

        if ($helper->isEnterprise() && abs((double)$discountObject->getGiftCardsAmount()) > 0) {
            $discount = abs((double)$discountObject->getGiftCardsAmount());
        }

        if (abs((double)$discountObject->getDiscountAmount()) > 0) {
            $discount += abs((double)$discountObject->getDiscountAmount());
        }

        if ($helper->isEnterprise() && abs((double)$discountObject->getCustomerBalanceAmount()) > 0) {
            $discount += abs((double)$discountObject->getCustomerBalanceAmount());
        }

        $discount = (-1 * round($discount, 2));

        return $discount;
    }

    /**
     * @param array $vars
     */
    private function addArticlesVariables(&$vars)
    {
        $i        = 1;
        $articles = array();

        $productArticles = $this->getProductArticles($i);
        $i += count($productArticles);
        $articles = array_merge($articles, $productArticles);

        $enterpriseArticles = $this->getEnterpriseArticles($i);
        $i += count($enterpriseArticles);
        $articles = array_merge($articles, $enterpriseArticles);

        $fee    = (double) $this->_order->getBuckarooFee();
        $feeTax = (double) $this->_order->getBuckarooFeeTax();
        $paymentFeeArticles = $this->getPaymentFeeLine($fee, $feeTax, $i);
        if (!empty($paymentFeeArticles)) {
            $articles[] = $paymentFeeArticles;
            $i++;
        }

        $discountArticle = $this->getDiscountArticle($i);
        if (!empty($discountArticle)) {
            $articles[] = $discountArticle;
            $i++;
        }
        $shippingCosts = round($this->_order->getBaseShippingInclTax(), 2);
        $shippingArticle = $this->getShippingArticle($shippingCosts, $i);
        if (!empty($shippingArticle)) {
            $articles[] = $shippingArticle;
        }

        $requestArray = array('Articles' => $articles);

        if (array_key_exists('customVars', $vars) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $requestArray);
        } else {
            $vars['customVars'][$this->_method] = $requestArray;
        }
    }

    /**
     * @param $i
     *
     * @return array
     */
    private function getProductArticles($i)
    {
        $products = $this->_order->getAllItems();
        $max      = 99;
        $productArticles = array();

        /** @var $item Mage_Sales_Model_Order_Item */
        foreach ($products as $item) {
            if (empty($item) || $item->hasParentItemId()) {
                continue;
            }

            $productArticles[] = $this->getSingleProductArticle($item, $i++);

            if ($i > $max) {
                break;
            }
        }

        return $productArticles;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     * @param int                         $groupId
     *
     * @return array
     */
    private function getSingleProductArticle($item, $groupId = 1)
    {
        // Changed calculation from unitPrice to orderLinePrice due to impossible to recalculate unitprice,
        // because of differences in outcome between TAX settings: Unit, OrderLine and Total.
        // Quantity will always be 1 and quantity ordered will be in the article description.
        $productPrice = ($item->getBasePrice() * $item->getQtyOrdered())
            + $item->getBaseTaxAmount()
            + $item->getBaseHiddenTaxAmount();
        $productPrice = round($productPrice, 2);

        $description = (int) $item->getQtyOrdered() . 'x ' . $item->getName();

        $article = array();
        $article[] = $this->getParameterLine('Description', $description, 'Article', $groupId);
        $article[] = $this->getParameterLine('GrossUnitPrice', $productPrice, 'Article', $groupId);
        $article[] = $this->getParameterLine('VatPercentage', round($item->getTaxPercent(), 2), 'Article', $groupId);
        $article[] = $this->getParameterLine('Quantity', 1, 'Article', $groupId);
        $article[] = $this->getParameterLine('Identifier', $item->getId(), 'Article', $groupId);
        $article[] = $this->getParameterLine('ImageUrl', $item->getProduct()->getImageUrl(), 'Article', $groupId);
        $article[] = $this->getParameterLine('Url', $item->getProduct()->getProductUrl(), 'Article', $groupId);

        return $article;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param                                $i
     *
     * @return array
     */
    private function getCreditmemoProductArticles($payment, $i)
    {
        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();
        $products = $creditmemo->getAllItems();
        $max      = 99;
        $productArticles    = array();

        /** @var Mage_Sales_Model_Order_Creditmemo_Item $item */
        foreach ($products as $item) {
            if (empty($item) || ($item->getOrderItem() && $item->getOrderItem()->getParentItem())) {
                continue;
            }

            $productArticles[] = $this->getSingleCreditmemoProductArticle($item, $i++);

            if ($i > $max) {
                break;
            }
        }

        return $productArticles;
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo_Item $item
     * @param $groupId
     *
     * @return array
     */
    private function getSingleCreditmemoProductArticle($item, $groupId)
    {
        $orderItem = $item->getOrderItem();
        $description = (int) $item->getQty() . 'x ' . $item->getName();
        $productPrice = $item->getRowTotalInclTax() - $item->getDiscountAmount();
        $taxPercent = round($orderItem->getTaxPercent(), 2);

        $article = array();
        $article[] = $this->getParameterLine('RefundType', 'Return', 'Article', $groupId);
        $article[] = $this->getParameterLine('Description', $description, 'Article', $groupId);
        $article[] = $this->getParameterLine('GrossUnitPrice', $productPrice, 'Article', $groupId);
        $article[] = $this->getParameterLine('VatPercentage', $taxPercent, 'Article', $groupId);
        $article[] = $this->getParameterLine('Quantity', 1, 'Article', $groupId);
        $article[] = $this->getParameterLine('Identifier', $item->getOrderItemId(), 'Article', $groupId);
        //$article[] = $this->getParameterLine('ImageUrl', $orderItem->getProduct()->getImageUrl(), 'Article', $groupId);
        $article[] = $this->getParameterLine('Url', $orderItem->getProduct()->getProductUrl(), 'Article', $groupId);

        return $article;
    }

    /**
     * @param $groupId
     *
     * @return array
     */
    private function getEnterpriseArticles($groupId)
    {
        $articles = array();
        $helper = $this->getHelper();

        if (!$helper->isEnterprise()) {
            return $articles;
        }

        $gwId = 1;
        $gwTaxClass = Mage::helper('enterprise_giftwrapping')->getWrappingTaxClass($this->_order->getStoreId());
        $gwTax = $this->getTaxPercent($gwTaxClass);

        if ($this->_order->getGwBasePrice() > 0) {
            $gwPrice = $this->_order->getGwBasePrice() + $this->_order->getGwBaseTaxAmount();
            $description = $helper->__('Gift Wrapping for Order');

            $gwArticle = array();
            $gwArticle[] = $this->getParameterLine('Description', $description, 'Article', $groupId);
            $gwArticle[] = $this->getParameterLine('GrossUnitPrice', $gwPrice, 'Article', $groupId);
            $gwArticle[] = $this->getParameterLine('VatPercentage', $gwTax, 'Article', $groupId);
            $gwArticle[] = $this->getParameterLine('Quantity', 1, 'Article', $groupId);
            $gwArticle[] = $this->getParameterLine('Identifier', 'gwo_' . $this->_order->getGwId(), 'Article', $groupId);

            $articles[] = $gwArticle;

            $gwId += $this->_order->getGwId();
            $groupId++;
        }

        if ($this->_order->getGwItemsBasePrice() > 0) {
            $gwiPrice = $this->_order->getGwItemsBasePrice() + $this->_order->getGwItemsBaseTaxAmount();
            $description = $helper->__('Gift Wrapping for Items');

            $gwiArticle = array();
            $gwiArticle[] = $this->getParameterLine('Description', $description, 'Article', $groupId);
            $gwiArticle[] = $this->getParameterLine('GrossUnitPrice', $gwiPrice, 'Article', $groupId);
            $gwiArticle[] = $this->getParameterLine('VatPercentage', $gwTax, 'Article', $groupId);
            $gwiArticle[] = $this->getParameterLine('Quantity', 1, 'Article', $groupId);
            $gwiArticle[] = $this->getParameterLine('Identifier', 'gwi_' . $gwId, 'Article', $groupId);

            $articles[] = $gwiArticle;
        }

        return $articles;
    }

    /**
     * @param $groupId
     *
     * @return array
     */
    private function getDiscountArticle($groupId)
    {
        $article = array();
        $discountObject = $this->_order;

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
        $invoiceCollection = $this->_order->getInvoiceCollection();

        if (!empty($invoiceCollection->getItems())) {
            $discountObject = $invoiceCollection->getLastItem();
        }

        $discount = $this->calculateDiscount($discountObject);

        if ($discount == 0) {
            return $article;
        }

        $article = array();
        $article[] = $this->getParameterLine('Description', 'Discount', 'Article', $groupId);
        $article[] = $this->getParameterLine('GrossUnitPrice', $discount, 'Article', $groupId);
        $article[] = $this->getParameterLine('VatPercentage', 0, 'Article', $groupId);
        $article[] = $this->getParameterLine('Quantity', 1, 'Article', $groupId);
        $article[] = $this->getParameterLine('Identifier', 'discount_1', 'Article', $groupId);

        return $article;
    }

    /**
     * @param float|double $shippingCosts
     * @param int $groupId
     *
     * @return array
     */
    private function getShippingArticle($shippingCosts, $groupId)
    {
        $article = array();

        if ($shippingCosts <= 0) {
            return $article;
        }

        $store = $this->_order->getStore();
        $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $store);
        $shippingTaxPercent = $this->getTaxPercent($shippingTaxClass);

        $article = array();
        $article[] = $this->getParameterLine('Description', 'Shipping fee', 'Article', $groupId);
        $article[] = $this->getParameterLine('GrossUnitPrice', $shippingCosts, 'Article', $groupId);
        $article[] = $this->getParameterLine('VatPercentage', $shippingTaxPercent, 'Article', $groupId);
        $article[] = $this->getParameterLine('Quantity', 1, 'Article', $groupId);
        $article[] = $this->getParameterLine('Identifier', 'shipping_1', 'Article', $groupId);

        return $article;
    }

    /**
     * @param $vars
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    protected function addCreditmemoArticlesVariables(&$vars, $payment)
    {
        $i     = 1;
        $articles = array();

        $productArticles = $this->getCreditmemoProductArticles($payment, $i);
        $i += count($productArticles);
        $articles = array_merge($articles, $productArticles);

        /** @var Mage_Sales_Model_Resource_Order_Creditmemo_Collection $creditmemoCollection */
        $creditmemoCollection = $this->_order->getCreditmemosCollection();

        if (count($creditmemoCollection) == 1) {
            $enterpriseArticles = $this->getEnterpriseArticles($i);
            $i += count($enterpriseArticles);
            $articles = array_merge($articles, $enterpriseArticles);
        }

        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();
        $fee    = (double) $creditmemo->getBuckarooFee();
        $feeTax = (double) $creditmemo->getBuckarooFeeTax();
        $paymentFeeArticles = $this->getPaymentFeeLine($fee, $feeTax, $i);
        if (!empty($paymentFeeArticles)) {
            $paymentFeeArticles[] = $this->getParameterLine('RefundType', 'Refund', 'Article', $i);
            $articles[] = $paymentFeeArticles;
            $i++;
        }

        $shippingCosts = round($creditmemo->getBaseShippingAmount() + $creditmemo->getBaseShippingTaxAmount(), 2);
        $shippingArticle = $this->getShippingArticle($shippingCosts, $i);
        if (!empty($shippingArticle)) {
            $shippingArticle[] = $this->getParameterLine('RefundType', 'Refund', 'Article', $i);
            $articles[] = $shippingArticle;
        }

        $requestArray = array('Articles' => $articles);

        if (array_key_exists('customVars', $vars) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $requestArray);
        } else {
            $vars['customVars'][$this->_method] = $requestArray;
        }
    }

    /**
     * @param double $fee
     * @param double $feeTax
     * @param $groupId
     *
     * @return array
     */
    private function getPaymentFeeLine($fee, $feeTax, $groupId)
    {
        $article = array();

        if ($fee <= 0) {
            return $article;
        }

        $feeTaxId = Mage::getStoreConfig('tax/classes/buckaroo_fee', $this->_order->getStoreId());
        $feeTaxPercentage = $this->getTaxPercent($feeTaxId);

        $article = array();
        $article[] = $this->getParameterLine('Description', 'Payment fee', 'Article', $groupId);
        $article[] = $this->getParameterLine('GrossUnitPrice', round($fee+$feeTax, 2), 'Article', $groupId);
        $article[] = $this->getParameterLine('VatPercentage', $feeTaxPercentage, 'Article', $groupId);
        $article[] = $this->getParameterLine('Quantity', 1, 'Article', $groupId);
        $article[] = $this->getParameterLine('Identifier', 1, 'Article', $groupId);

        return $article;
    }

    /**
     * @param $taxClassId
     *
     * @return float
     */
    private function getTaxPercent($taxClassId)
    {
        $store = $this->_order->getStore();
        $billingAddress = $this->_order->getBillingAddress();
        $shippingAddress = $this->_order->getShippingAddress();
        $customerTaxClass = $this->_order->getCustomerTaxClassId();

        /** @var Mage_Tax_Model_Calculation $taxCalculation */
        $taxCalculation = Mage::getModel('tax/calculation');

        $request = $taxCalculation->getRateRequest($shippingAddress, $billingAddress, $customerTaxClass, $store);
        $request->setProductClassId($taxClassId);

        $percent = $taxCalculation->getRate($request);
        return $percent;
    }

    /**
     * Checks if shipping-address is different from billing-address
     *
     * @return bool
     */
    protected function isShippingDifferent()
    {
        // exclude certain keys that are always different
        $excludeKeys = array(
            'entity_id', 'customer_address_id', 'quote_address_id',
            'region_id', 'customer_id', 'address_type'
        );

        //get both the order-addresses
        $oBillingAddress = $this->_order->getBillingAddress()->getData();
        $oShippingAddress = $this->_order->getShippingAddress();
        $oShippingAddress = (!$oShippingAddress ? $oBillingAddress : $oShippingAddress->getData());

        //remove the keys with corresponding values from both the addressess
        $oBillingAddressFiltered = array_diff_key($oBillingAddress, array_flip($excludeKeys));
        $oShippingAddressFiltered = array_diff_key($oShippingAddress, array_flip($excludeKeys));

        //differentiate the addressess, when some data is different an array with changes will be returned
        $addressDiff = array_diff($oBillingAddressFiltered, $oShippingAddressFiltered);

        if (!empty($addressDiff)) { // billing and shipping addresses are different
            return true;
        }

        return false;
    }
}
