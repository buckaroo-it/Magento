<?php
class TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay_Observer
    extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code = 'buckaroo3extended_afterpay';
    protected $_method = false;
    /** @var  TIG_Buckaroo3Extended_Helper_Data $_helper */
    protected $_helper;

    protected function _construct()
    {
        $this->_method = Mage::getStoreConfig(
            'buckaroo/' . $this->_code . '/paymethod', Mage::app()->getStore()->getStoreId()
        );
        $this->_helper = Mage::helper('buckaroo3extended');
    }

    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $vars = $request->getVars();

        if ($this->_method == false) {
            $this->_method = Mage::getStoreConfig(
                'buckaroo/' . $this->_code . '/paymethod', Mage::app()->getStore()->getStoreId()
            );
        }

        $paymentAction = Mage::getStoreConfig(
            'buckaroo/' . $this->_code . '/payment_action',
            Mage::app()->getStore()->getStoreId()
        );

        if ($paymentAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE) {
            $serviceAction = 'Authorize';
        } else {
            $serviceAction = 'Pay';
        }

        $array = array(
            $this->_method => array(
                'action'   => $serviceAction,
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

        $this->_addAfterpayVariables($vars, $this->_method);
        $this->_addArticlesVariables($vars, $this->_method);
        $this->_addShippingCostsVariables($vars, $this->_method);

        $request->setVars($vars);
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     * @throws Exception
     */
    public function buckaroo3extended_push_custom_processing(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $response = $observer->getResponse();
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        $paymentMethod = $order->getPayment()->getMethodInstance();

        // Authorize is successful
        if ($response['status'] == TIG_Buckaroo3Extended_Helper_Data::BUCKAROO_SUCCESS ||
            $paymentMethod->getConfigPaymentAction() == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE) {
            $newStates = $observer->getPush()->getNewStates($response['status']);
            $order->setState($newStates[0])
                  ->save();
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
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

    /** refund methods */

    /**
     * @param Varien_Event_Observer $observer
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
            'version'   => 1,

        );

        if ($this->_method == false) {
            $this->_method = Mage::getStoreConfig(
                'buckaroo/' . $this->_code . '/paymethod',
                Mage::app()->getStore()->getStoreId()
            );
        }

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

        $request = $observer->getRequest();
        $this->_order = $request->getOrder();
        $payment     = $request->getPayment();

        $vars = $request->getVars();

        $this->_addCreditmemoArticlesVariables($vars, $payment, $this->_method);

        /** @var Mage_Sales_Model_Order $tst */
        $tst = $request->getOrder();
        $tst->getPayment()->canRefundPartialPerInvoice();

        if ($this->_order->getPayment()->canRefundPartialPerInvoice() && $payment->getCreditmemo()) {
            $this->_addCreditmemoArticlesVariables($vars, $payment, $this->_method);
        }

        $request->setVars($vars);

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_capture_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $vars = $request->getVars();

        if ($this->_method == false) {
            $this->_method = Mage::getStoreConfig(
                'buckaroo/' . $this->_code . '/paymethod',
                Mage::app()->getStore()->getStoreId()
            );
        }

        $array = array(
            $this->_method => array(
                'action'   => 'Capture',
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
    public function buckaroo3extended_capture_request_addcustomvars(Varien_Event_Observer $observer)
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

        $this->_addAfterpayVariables($vars, $this->_method);

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
        $invoiceCollection = $this->_order->getInvoiceCollection();

        /** @var Mage_Sales_Model_Order_Invoice $lastInvoice */
        $lastInvoice = $invoiceCollection->getLastItem();

        if ($this->_order->getPayment()->canCapturePartial()
            && !empty($invoiceCollection)
            && $lastInvoice->getBaseGrandTotal() < $this->_order->getBaseGrandTotal()
        ) {
            $this->_addPartialArticlesVariables($vars, $this->_method);
        } else {
            $this->_addArticlesVariables($vars, $this->_method);
        }

        // Shipping costs only need to be send with the first invoice
        if (count($invoiceCollection) == 1) {
            $this->_addShippingCostsVariables($vars, $this->_method);
        }

        $request->setVars($vars);
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_cancelauthorize_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();

        $vars = $request->getVars();

        if ($this->_method == false) {
            $this->_method = Mage::getStoreConfig(
                'buckaroo/' . $this->_code . '/paymethod',
                Mage::app()->getStore()->getStoreId()
            );
        }

        $array = array(
            $this->_method => array(
                'action'   => 'CancelAuthorize',
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

    /** INTERNAL METHODS **/

    /**
     * Adds variables required for the SOAP XML for paymentguarantee to the variable array
     * Will merge with old array if it exists
     *
     * @param array $vars
     */
    protected function _addAfterpayVariables(&$vars)
    {
        $session            = Mage::getSingleton('checkout/session');
        $additionalFields   = $session->getData('additionalFields');

        $paymentAdditionalInformation = $this->_order->getPayment()->getAdditionalInformation();
        if (is_array($paymentAdditionalInformation)
            && !empty($paymentAdditionalInformation)
            && isset($paymentAdditionalInformation['BPE_AccountNumber'])
            && $paymentAdditionalInformation['BPE_AccountNumber'] != ""
        ) {
            $additionalFields = $paymentAdditionalInformation;
        }

        $requestArray       = array();

        //add billing address
        $billingAddress     = $this->_order->getBillingAddress();
        $streetFull         = $this->_processAddress($billingAddress->getStreetFull());
        $rawPhoneNumber     = $billingAddress->getTelephone();

        if (!is_numeric($rawPhoneNumber) || $rawPhoneNumber == '-') {
            $rawPhoneNumber = $additionalFields['BPE_PhoneNumber'];
        }

        $billingPhonenumber = ($billingAddress->getCountryId() == 'BE'
            ? $this->_processPhoneNumberBe($rawPhoneNumber) : $this->_processPhoneNumber($rawPhoneNumber));
        $billingInfo = array(
            'BillingTitle'             => $billingAddress->getFirstname(),
            'BillingGender'            => $additionalFields['BPE_Customergender'],
            'BillingInitials'          => strtoupper(substr($billingAddress->getFirstname(), 0, 1)),
            'BillingLastName'          => $billingAddress->getLastname(),
            'BillingBirthDate'         => $additionalFields['BPE_customerbirthdate'],
            'BillingStreet'            => $streetFull['street'],
            'BillingHouseNumber'       => $streetFull['house_number'],
            'BillingHouseNumberSuffix' => $streetFull['number_addition'],
            'BillingPostalCode'        => $billingAddress->getPostcode(),
            'BillingCity'              => $billingAddress->getCity(),
            'BillingRegion'            => $billingAddress->getRegion(),
            'BillingCountry'           => $billingAddress->getCountryId(),
            'BillingEmail'             => $billingAddress->getEmail(),
            'BillingPhoneNumber'       => $billingPhonenumber['clean'],
            'BillingLanguage'          => $billingAddress->getCountryId(),
        );
        $requestArray = array_merge($requestArray, $billingInfo);

        // Compatible with postnl pakjegemak
        $pakjeGemakAddress = false;
        if (Mage::helper('core')->isModuleEnabled('TIG_PostNL')) {
            $addresses = $this->_order->getAddressesCollection();

            foreach ($addresses as $addressNew) {
                if ($addressNew->getAddressType() == 'pakje_gemak') {
                    $pakjeGemakAddress = $addressNew;
                    break;
                }
            }
        }

        //add shipping address (only when different from billing address)
        if ($this->isShippingDifferent() || $pakjeGemakAddress) {
            $shippingAddress     = $this->_order->getShippingAddress();
            $streetFull          = $this->_processAddress($shippingAddress->getStreetFull());
            $shippingPhonenumber = ($shippingAddress->getCountryId() ?
                $this->_processPhoneNumberBe($shippingAddress->getTelephone()) :
                $this->_processPhoneNumber($shippingAddress->getTelephone()));

            $shippingInfo = array(
                'AddressesDiffer'           => 'true',
                'ShippingTitle'             => $shippingAddress->getFirstname(),
                'ShippingGender'            => $additionalFields['BPE_Customergender'],
                'ShippingInitials'          => strtoupper(substr($shippingAddress->getFirstname(), 0, 1)),
                'ShippingLastName'          => $shippingAddress->getLastname(),
                'ShippingBirthDate'         => $additionalFields['BPE_customerbirthdate'],
                'ShippingStreet'            => $streetFull['street'],
                'ShippingHouseNumber'       => $streetFull['house_number'],
                'ShippingHouseNumberSuffix' => $streetFull['number_addition'],
                'ShippingPostalCode'        => $shippingAddress->getPostcode(),
                'ShippingCity'              => $shippingAddress->getCity(),
                'ShippingRegion'            => $shippingAddress->getRegion(),
                'ShippingCountryCode'       => $shippingAddress->getCountryId(),
                'ShippingEmail'             => $shippingAddress->getEmail(),
                'ShippingPhoneNumber'       => $shippingPhonenumber['clean'],
                'ShippingLanguage'          => $shippingAddress->getCountryId(),
            );

            //Update with pakjeGemak values
            if ($pakjeGemakAddress) {
                $streetPakjeGemak = $this->_processAddress($pakjeGemakAddress->getStreetFull());

                $shippingInfo['ShippingTitle']             = 'A';
                $shippingInfo['ShippingLastName']          = 'POSTNL afhaalpunt ' . $pakjeGemakAddress->getCompany();
                $shippingInfo['ShippingStreet']            = $streetPakjeGemak['street'];
                $shippingInfo['ShippingHouseNumber']       = $streetPakjeGemak['house_number'];
                $shippingInfo['ShippingHouseNumberSuffix'] = $streetPakjeGemak['number_addition'];
                $shippingInfo['ShippingPostalCode']        = $pakjeGemakAddress->getPostcode();
                $shippingInfo['ShippingCity']              = $pakjeGemakAddress->getCity();
                $shippingInfo['ShippingCountryCode']       = $pakjeGemakAddress->getCountryId();
                $shippingInfo['ShippingPhoneNumber']       = $pakjeGemakAddress->getTelephone();
            }

            $requestArray = array_merge($requestArray, $shippingInfo);
        }

        //customer info
        $customerInfo = array(
            'CustomerAccountNumber' => $additionalFields['BPE_AccountNumber'],
            'CustomerIPAddress'     => Mage::helper('core/http')->getRemoteAddr(),
            'Accept'                => $additionalFields['BPE_Accept'],
        );

        /** @var Mage_Sales_Model_Order|Mage_Sales_Model_Order_Invoice $discountData */
        $discountData = $this->_order;

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
        $invoiceCollection = $this->_order->getInvoiceCollection();

        if (count($invoiceCollection) > 0) {
            $discountData = $invoiceCollection->getLastItem();
        }

        $discount = $this->calculateDiscount($discountData);

        //add order Info
        $orderInfo = array(
            'Discount'      => $discount,
        );

        $requestArray = array_merge($requestArray, $customerInfo);
        $requestArray = array_merge($requestArray, $orderInfo);
        //is B2B
        $requestArray = $this->handleBtoB($additionalFields, $requestArray);

        if (array_key_exists('customVars', $vars) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $requestArray);
        } else {
            $vars['customVars'][$this->_method] = $requestArray;
        }
    }

    protected function handleBtoB($additionalFields, $requestArray)
    {
        if ($additionalFields['BPE_B2B'] == 2) {
            $businessToBusinessInfo = array(
                'B2B'                    => 'true',
                'CompanyCOCRegistration' => $additionalFields['BPE_CompanyCOCRegistration'],
                'CompanyName'            => $additionalFields['BPE_CompanyName'],
            );
            $requestArray = array_merge($requestArray, $businessToBusinessInfo);
        }

        return $requestArray;
    }

    protected function calculateDiscount($discountData)
    {
        $discount = null;

        if (Mage::helper('buckaroo3extended')->isEnterprise()) {
            if ((double)$discountData->getGiftCardsAmount() > 0) {
                $discount = (double)$discountData->getGiftCardsAmount();
            }
        }

        if (abs((double)$discountData->getDiscountAmount()) > 0) {
            $discount += abs((double)$discountData->getDiscountAmount());
        }

        if (Mage::helper('buckaroo3extended')->isEnterprise()
            && abs((double)$discountData->getCustomerBalanceAmount()) > 0) {
            $discount += abs((double)$discountData->getCustomerBalanceAmount());
        }

        return round($discount, 2);
    }

    /**
     * @param $vars
     */
    protected function _addShippingCostsVariables(&$vars)
    {
        $shippingCosts = round($this->_order->getBaseShippingInclTax(), 2);

        $orderInfo = array(
            'ShippingCosts' => $shippingCosts,
        );

        if (array_key_exists('customVars', $vars) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $orderInfo);
        } else {
            $vars['customVars'][$this->_method] = $orderInfo;
        }
    }

    /**
     * @param array $vars
     */
    protected function _addArticlesVariables(&$vars)
    {
        //add all products max 10
        $products = $this->_order->getAllItems();
        $max      = 99;
        $i        = 1;
        $group    = array();

        foreach ($products as $item) {
            /** @var $item Mage_Sales_Model_Order_Item */

            if (empty($item) || $item->hasParentItemId()) {
                continue;
            }

            // Changed calculation from unitPrice to orderLinePrice due to impossible to recalculate unitprice,
            // because of differences in outcome between TAX settings: Unit, OrderLine and Total.
            // Quantity will always be 1 and quantity ordered will be in the article description.
            $productPrice = ($item->getBasePrice() * $item->getQtyOrdered())
                + $item->getBaseTaxAmount()
                + $item->getBaseHiddenTaxAmount();
            $productPrice = round($productPrice, 2);


            $article['ArticleDescription']['value'] = (int) $item->getQtyOrdered() . 'x ' . $item->getName();
            $article['ArticleId']['value']          = $item->getId();
            $article['ArticleQuantity']['value']    = 1;
            $article['ArticleUnitPrice']['value']   = $productPrice;
            $article['ArticleVatcategory']['value'] = $this->_getTaxCategory($this->_getTaxClassId($item));

            $group[$i] = $article;


            if ($i <= $max) {
                $i++;
                continue;
            }

            break;
        }

        if (Mage::helper('buckaroo3extended')->isEnterprise()) {
            $gwId = 1;
            $gwTax = Mage::helper('enterprise_giftwrapping')->getWrappingTaxClass($this->_order->getStoreId());

            if ($this->_order->getGwBasePrice() > 0) {
                $gwPrice = $this->_order->getGwBasePrice() + $this->_order->getGwBaseTaxAmount();

                $gwOrder = array();
                $gwOrder['ArticleDescription']['value'] =
                    Mage::helper('buckaroo3extended')->__('Gift Wrapping for Order');
                $gwOrder['ArticleId']['value'] = 'gwo_' . $this->_order->getGwId();
                $gwOrder['ArticleQuantity']['value'] = 1;
                $gwOrder['ArticleUnitPrice']['value'] = $gwPrice;
                $gwOrder['ArticleVatcategory']['value'] = $gwTax;

                $group[] = $gwOrder;

                $gwId += $this->_order->getGwId();
            }

            if ($this->_order->getGwItemsBasePrice() > 0) {
                $gwiPrice = $this->_order->getGwItemsBasePrice() + $this->_order->getGwItemsBaseTaxAmount();

                $gwiOrder = array();
                $gwiOrder['ArticleDescription']['value'] =
                    Mage::helper('buckaroo3extended')->__('Gift Wrapping for Items');
                $gwiOrder['ArticleId']['value'] = 'gwi_' . $gwId;
                $gwiOrder['ArticleQuantity']['value'] = 1;
                $gwiOrder['ArticleUnitPrice']['value'] = $gwiPrice;
                $gwiOrder['ArticleVatcategory']['value'] = $gwTax;

                $group[] = $gwiOrder;
            }
        }

        end($group);// move the internal pointer to the end of the array
        $key             = (int)key($group);
        $feeGroupId      = $key+1;
        $paymentFeeArray = $this->_getPaymentFeeLine();

        if (false !== $paymentFeeArray && is_array($paymentFeeArray)) {
            $group[$feeGroupId] = $paymentFeeArray;
        }

        $requestArray = array('Articles' => $group);

        if (array_key_exists('customVars', $vars) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $requestArray);
        } else {
            $vars['customVars'][$this->_method] = $requestArray;
        }
    }

    /**
     * @param array $vars
     */
    protected function _addPartialArticlesVariables(&$vars)
    {
        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
        $invoiceCollection = $this->_order->getInvoiceCollection();

        $products = $invoiceCollection->getLastItem()->getAllItems();
        $max      = 99;
        $i        = 1;
        $group    = array();

        /** @var Mage_Sales_Model_Order_Invoice_Item $item */
        foreach ($products as $item) {
            if (empty($item) || ($item->getOrderItem() && $item->getOrderItem()->getParentItem())) {
                continue;
            }

            // Changed calculation from unitPrice to orderLinePrice due to impossible to recalculate unitprice,
            // because of differences in outcome between TAX settings: Unit, OrderLine and Total.
            // Quantity will always be 1 and quantity ordered will be in the article description.
            $productPrice = ($item->getBasePrice() * $item->getQty())
                + $item->getBaseTaxAmount()
                + $item->getBaseHiddenTaxAmount();
            $productPrice = round($productPrice, 2);

            $article['ArticleDescription']['value'] = (int) $item->getQty() . 'x ' . $item->getName();
            $article['ArticleId']['value']          = $item->getOrderItemId();
            $article['ArticleQuantity']['value']    = 1;
            $article['ArticleUnitPrice']['value']   = $productPrice;
            $article['ArticleVatcategory']['value'] = $this->_getTaxCategory($this->_getTaxClassId($item));

            $group[$i] = $article;


            if ($i <= $max) {
                $i++;
                continue;
            }

            break;
        }

        if (Mage::helper('buckaroo3extended')->isEnterprise() && count($invoiceCollection) == 1) {
            $gwId = 1;
            $gwTax = Mage::helper('enterprise_giftwrapping')->getWrappingTaxClass($this->_order->getStoreId());

            if ($this->_order->getGwBasePrice() > 0) {
                $gwPrice = $this->_order->getGwBasePrice() + $this->_order->getGwBaseTaxAmount();

                $gwOrder = array();
                $gwOrder['ArticleDescription']['value'] =
                    Mage::helper('buckaroo3extended')->__('Gift Wrapping for Order');
                $gwOrder['ArticleId']['value'] = 'gwo_' . $this->_order->getGwId();
                $gwOrder['ArticleQuantity']['value'] = 1;
                $gwOrder['ArticleUnitPrice']['value'] = $gwPrice;
                $gwOrder['ArticleVatcategory']['value'] = $gwTax;

                $group[] = $gwOrder;

                $gwId += $this->_order->getGwId();
            }

            if ($this->_order->getGwItemsBasePrice() > 0) {
                $gwiPrice = $this->_order->getGwItemsBasePrice() + $this->_order->getGwItemsBaseTaxAmount();

                $gwiOrder = array();
                $gwiOrder['ArticleDescription']['value'] =
                    Mage::helper('buckaroo3extended')->__('Gift Wrapping for Items');
                $gwiOrder['ArticleId']['value'] = 'gwi_' . $gwId;
                $gwiOrder['ArticleQuantity']['value'] = 1;
                $gwiOrder['ArticleUnitPrice']['value'] = $gwiPrice;
                $gwiOrder['ArticleVatcategory']['value'] = $gwTax;

                $group[] = $gwiOrder;
            }
        }

        end($group);// move the internal pointer to the end of the array
        $key             = (int)key($group);
        $feeGroupId      = $key+1;
        $paymentFeeArray = $this->_getPaymentFeeLine();

        if (false !== $paymentFeeArray && is_array($paymentFeeArray) && count($invoiceCollection) == 1) {
            $group[$feeGroupId] = $paymentFeeArray;
        }

        $requestArray = array('Articles' => $group);

        if (array_key_exists('customVars', $vars) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $requestArray);
        } else {
            $vars['customVars'][$this->_method] = $requestArray;
        }
    }

    protected function _addCreditmemoArticlesVariables(&$vars, $payment)
    {
        /** @var Mage_Sales_Model_Resource_Order_Creditmemo_Collection $creditmemoCollection */
        $creditmemoCollection = $this->_order->getCreditmemosCollection();

        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();
        $products = $creditmemo->getAllItems();
        $max      = 99;
        $i        = 1;
        $group    = array();

        /** @var Mage_Sales_Model_Order_Creditmemo_Item $item */
        foreach ($products as $item) {
            if (empty($item) || ($item->getOrderItem() && $item->getOrderItem()->getParentItem())) {
                continue;
            }

            $article['ArticleDescription']['value'] = (int) $item->getQty() . 'x ' . $item->getName();
            $article['ArticleId']['value']          = $item->getOrderItemId();
            $article['ArticleQuantity']['value']    = 1;
            $article['ArticleUnitPrice']['value']   = $item->getRowTotalInclTax() - $item->getDiscountAmount();
            $article['ArticleVatcategory']['value'] =
                $this->_getTaxCategory($this->_getTaxClassId($item->getOrderItem()));

            $group[$i] = $article;

            if ($i <= $max) {
                $i++;
                continue;
            }

            break;
        }

        $group = $this->handleEnterprise($group, $creditmemoCollection);

        end($group);// move the internal pointer to the end of the array
        $key = (int)key($group);
        $fee = (double) $creditmemo->getBuckarooFee();

        if ($fee > 0) {
            $feeTax = (double) $creditmemo->getBuckarooFeeTax();

            $feeArticle['ArticleDescription']['value'] = 'Servicekosten';
            $feeArticle['ArticleId']['value']          = 1;
            $feeArticle['ArticleQuantity']['value']    = 1;
            $feeArticle['ArticleUnitPrice']['value']   = round($fee + $feeTax, 2);
            $feeArticle['ArticleVatcategory']['value'] = $this->_getTaxCategory(
                Mage::getStoreConfig('tax/classes/buckaroo_fee', Mage::app()->getStore()->getId())
            );

            $feeGroupId = $key+1;
            $group[$feeGroupId] = $feeArticle;
        }

        $requestArray = array('Articles' => $group);

        if (array_key_exists('customVars', $vars) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $requestArray);
        } else {
            $vars['customVars'][$this->_method] = $requestArray;
        }

        $shippingCosts = round($creditmemo->getBaseShippingAmount() + $creditmemo->getBaseShippingTaxAmount(), 2);

        if ($shippingCosts > 0) {
            $shippingInfo = array(
                'ShippingCosts' => $shippingCosts,
            );

            if (array_key_exists('customVars', $vars) && is_array($vars['customVars'][$this->_method])) {
                $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $shippingInfo);
            } else {
                $vars['customVars'][$this->_method] = $shippingInfo;
            }
        }
    }

    protected function handleEnterprise($group, $creditmemoCollection)
    {
        if (Mage::helper('buckaroo3extended')->isEnterprise() && count($creditmemoCollection) == 1) {
            $gwId = 1;
            $gwTax = Mage::helper('enterprise_giftwrapping')->getWrappingTaxClass($this->_order->getStoreId());

            if ($this->_order->getGwBasePrice() > 0) {
                $gwPrice = $this->_order->getGwBasePrice() + $this->_order->getGwBaseTaxAmount();

                $gwOrder = array();
                $gwOrder['ArticleDescription']['value'] =
                    Mage::helper('buckaroo3extended')->__('Gift Wrapping for Order');
                $gwOrder['ArticleId']['value'] = 'gwo_' . $this->_order->getGwId();
                $gwOrder['ArticleQuantity']['value'] = 1;
                $gwOrder['ArticleUnitPrice']['value'] = $gwPrice;
                $gwOrder['ArticleVatcategory']['value'] = $gwTax;

                $group[] = $gwOrder;

                $gwId += $this->_order->getGwId();
            }

            if ($this->_order->getGwItemsBasePrice() > 0) {
                $gwiPrice = $this->_order->getGwItemsBasePrice() + $this->_order->getGwItemsBaseTaxAmount();

                $gwiOrder = array();
                $gwiOrder['ArticleDescription']['value'] =
                    Mage::helper('buckaroo3extended')->__('Gift Wrapping for Items');
                $gwiOrder['ArticleId']['value'] = 'gwi_' . $gwId;
                $gwiOrder['ArticleQuantity']['value'] = 1;
                $gwiOrder['ArticleUnitPrice']['value'] = $gwiPrice;
                $gwiOrder['ArticleVatcategory']['value'] = $gwTax;

                $group[] = $gwiOrder;
            }
        }

        return $group;
    }

    protected function _getPaymentFeeLine()
    {
        $fee    = (double) $this->_order->getBuckarooFee();
        $feeTax = (double) $this->_order->getBuckarooFeeTax();

        if ($fee > 0) {
            $article['ArticleDescription']['value'] = 'Servicekosten';
            $article['ArticleId']['value']          = 1;
            $article['ArticleQuantity']['value']    = 1;
            $article['ArticleUnitPrice']['value']   = round($fee+$feeTax, 2);
            $article['ArticleVatcategory']['value'] = $this->_getTaxCategory(
                Mage::getStoreConfig('tax/classes/buckaroo_fee', Mage::app()->getStore()->getId())
            );

            return $article;
        }

        return false;
    }

    /**
     * @param Mage_Sales_Model_Order_Item|Mage_Sales_Model_Order_Invoice_Item $item
     * @return array|bool|string
     */
    protected function _getTaxClassId($item)
    {
        return Mage::getResourceModel('catalog/product')->getAttributeRawValue(
            $item->getProductId(),
            'tax_class_id',
            $item->getStoreId()
        );
    }

    protected function _getTaxCategory($taxClassId)
    {
        if (!$taxClassId) {
            return 4;
        }

        $highTaxClasses = explode(
            ',', Mage::getStoreConfig('buckaroo/' . $this->_code . '/high', Mage::app()->getStore()->getStoreId())
        );
        $middleTaxClasses = explode(
            ',', Mage::getStoreConfig('buckaroo/' . $this->_code . '/middle', Mage::app()->getStore()->getStoreId())
        );
        $lowTaxClasses = explode(
            ',', Mage::getStoreConfig('buckaroo/' . $this->_code . '/low', Mage::app()->getStore()->getStoreId())
        );
        $zeroTaxClasses = explode(
            ',', Mage::getStoreConfig('buckaroo/' . $this->_code . '/zero', Mage::app()->getStore()->getStoreId())
        );
        $noTaxClasses = explode(
            ',', Mage::getStoreConfig('buckaroo/' . $this->_code . '/no', Mage::app()->getStore()->getStoreId())
        );

        if (in_array($taxClassId, $highTaxClasses)) {
            return 1;
        } elseif (in_array($taxClassId, $middleTaxClasses)) {
            return 5;
        } elseif (in_array($taxClassId, $lowTaxClasses)) {
            return 2;
        } elseif (in_array($taxClassId, $zeroTaxClasses)) {
            return 3;
        } else {
            return 4;
        }
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

    /**
     * @param $telephoneNumber
     * @return array
     */
    protected function _processPhoneNumberBe($telephoneNumber)
    {
        $number = $telephoneNumber;

        //the final output must like this: 003212345678 for mobile: 0032461234567
        //so 13 characters max else number is not valid
        //but for some error correction we try to find if there is some faulty notation

        $return = array("orginal" => $number, "clean" => false, "mobile" => false, "valid" => false);

        //first strip out the non-numeric characters:
        $match = preg_replace('/[^0-9]/Uis', '', $number);
        if ($match) {
            $number = $match;
        }

        $return['mobile'] = $this->_isMobileNumberBe($number);
        $numberLength = strlen((string)$number);

        if (($return['mobile'] && $numberLength == 13) || (!$return['mobile'] && $numberLength == 12)) {
            //if the length equal to 12 or 13 is, then we can check if the number is valid
            $return['valid'] = true;
            $return['clean'] = $number;
        } elseif ($numberLength > 13 || (!$return['mobile'] && $numberLength > 12)) {
            //if the number is bigger then 13, it means that there are probably a zero to much
            $return['clean'] = $this->_isValidNotationBe($number);
            $cleanLength = strlen((string)$return['clean']);

            if (($return['mobile'] && $cleanLength == 13) || (!$return['mobile'] && $cleanLength == 12)) {
                $return['valid'] = true;
            }
        } elseif (($return['mobile'] && ($numberLength == 11 || $numberLength == 12))
            || (!$return['mobile'] && ($numberLength == 10 || $numberLength == 11))
        ) {
            //if the number is equal to 10, 11 or 12, it means that they used a + in their number instead of 00
            $return['clean'] = $this->_isValidNotationBe($number);
            $cleanLength = strlen((string)$return['clean']);

            if (($return['mobile'] && $cleanLength == 13) || (!$return['mobile'] && $cleanLength == 12)) {
                $return['valid'] = true;
            }
        } elseif (($return['mobile'] && $numberLength == 10) || (!$return['mobile'] && $numberLength == 9)) {
            //this means that the user has no trailing "0032" and therfore only
            $return['clean'] = '0032'.substr($number, 1);
            $cleanLength = strlen((string)$return['clean']);

            if (($return['mobile'] && $cleanLength == 13) || (!$return['mobile'] && $cleanLength == 12)) {
                $return['valid'] = true;
            }
        } else {
            $return['mobile'] = $this->_isMobileNumberBe($number);
            //now we can almost say that the number is valid
            $return['valid'] = true;
            $return['clean'] = $number;
        }

        return $return;
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
        $oShippingAddress = $this->_order->getShippingAddress()->getData();

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
