<?php

/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
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
class TIG_Buckaroo3Extended_CheckoutController extends Mage_Core_Controller_Front_Action
{
    public function checkoutAction()
    {
        /** @var TIG_Buckaroo3Extended_Model_Request_Abstract $request */
        $request = Mage::getModel('buckaroo3extended/request_abstract');
        $request->sendRequest();
    }
    
    /**
     * Apple Pay Controller [checkout]
     */
    public function applepayAction()
    {
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart         = Mage::getModel('checkout/cart');
        $quote        = $cart->getQuote();
        $store        = $quote->getStore();
        $address      = $quote->getShippingAddress();
        $shippingData = $address->getData();
        $localeCode   = Mage::app()->getLocale()->getLocaleCode();
        $shortLocale  = explode('_', $localeCode)[0];
        
        $storeName    = $store->getFrontendName();
        $currencyCode = $quote->getStoreCurrencyCode();
        $guid         = Mage::getStoreConfig('buckaroo/buckaroo3extended/guid', $store->getId());
        
        $shippingData['culture_code']        = $shortLocale;
        $shippingData['currency_code']       = $currencyCode;
        $shippingData['guid']                = $guid;
        $shippingData['store_name']          = $storeName;
        $shippingData['calculated_subtotal'] = $shippingData['subtotal_incl_tax'];
        $shippingData['discount']            = isset($shippingData['discount_amount']) ? $shippingData['discount_amount'] : null;
        
        if (count($address->getAppliedTaxes()) == 0) {
            $shippingData['calculated_subtotal'] = $shippingData['subtotal'];
        }
        
        /** @var TIG_Buckaroo3Extended_Model_PaymentMethods_Applepay_Process $process */
        $process                     = Mage::getModel(' buckaroo3extended/paymentMethods_applepay_process');
        $shippingData['payment_fee'] = $process->calculateBuckarooFee($address);
        
        /** @var Mage_Core_Helper_Data $coreHelper $coreHelper */
        $coreHelper = Mage::helper('core');
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody($coreHelper->jsonEncode($shippingData));
    }
    
    /**
     * Creates a quote within product view for further processing.
     * Used by Apple Pay.
     *
     * @throws \Mage_Core_Exception
     * @throws \Mage_Core_Model_Store_Exception
     */
    public function addToCartAction()
    {
        /** @var TIG_Buckaroo3Extended_Model_PaymentMethods_Applepay_Process $process */
        $process  = Mage::getModel(' buckaroo3extended/paymentMethods_applepay_process');
        $postData = $this->getRequest()->getPost() ?: $process->sanitizeParams($_GET);
        if (!$postData['product']) {
            return;
        }
        
        $product = $postData['product'];
        
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = Mage::getModel('checkout/cart');
        $cart->truncate();
        $cart->init();
        
        /** @var Mage_Catalog_Model_Product $productCollection */
        $productCollection = Mage::getModel('catalog/product')->load($product['id']);
        
        /**
         * If product is configurable, build an array of the selected options.
         */
        $options = array();
        if ($productCollection->isConfigurable()) {
            $form             = array_column($postData['product']['options'], 'value', 'name');
            $availableOptions = $productCollection->getTypeInstance(true)->getConfigurableAttributes($productCollection)->getItems();
            $selectedOptions  = array_filter(
                $form, function ($name, $value) {
                return (strpos($value, 'super_attribute') !== false);
            }, ARRAY_FILTER_USE_BOTH
            );
            
            foreach ($availableOptions as $option) {
                $id = $option->getAttributeId();
                if (array_key_exists("super_attribute[$id]", $selectedOptions)) {
                    $options[$id] = $selectedOptions["super_attribute[$id]"];
                }
            }
        }
        
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getModel('checkout/session');
        
        try {
            $cart->addProduct($productCollection, array(
                'product_id'      => $product['id'],
                'qty'             => $product['qty'],
                'super_attribute' => $options
            ));
        } catch (Mage_Core_Exception $e) {
            $session->addError($this->__('Payment failed: ') . $e->getMessage());
            throw new $e;
        }
        
        $cart->save();
        
        $this->loadShippingMethodsAction();
    }
    
    /**
     * Load Shipping Methods (on Shipping Contact change)
     *
     * @throws \Mage_Core_Exception
     * @throws \Mage_Core_Model_Store_Exception
     */
    public function loadShippingMethodsAction()
    {
        /** @var TIG_Buckaroo3Extended_Model_PaymentMethods_Applepay_Process $process */
        $process  = Mage::getModel(' buckaroo3extended/paymentMethods_applepay_process');
        $postData = Mage::app()->getRequest()->getPost() ?: $process->sanitizeParams($_GET);
        $wallet   = array();
        if ($postData['wallet']) {
            $wallet = $postData['wallet'];
        }
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getModel('checkout/session');
        $quote   = $session->getQuote();
        
        /** @var Mage_Sales_Model_Quote_Address $address */
        $address = $quote->getShippingAddress();
        $shippingAddress = $process->processAddressFromWallet($wallet, 'shipping');
        
        $address->addData($shippingAddress);
        $quote->setShippingAddress($address);
        $session->setEstimatedShippingAddressData($shippingAddress);
        /**
         * Apparently this affects the loading of the shipping methods and differs in existing or new sessions.
         * It's important the quote is saved after setting this parameter.
         */
        $address->setCollectShippingRates(true);
        
        $quote->getPayment()->importData(array('method' => 'buckaroo3extended_applepay'));
        $quote->setCurrency(Mage::app()->getStore()->getBaseCurrencyCode());
        $quote->save();
        
        /** @var Mage_Checkout_Model_Cart_Shipping_Api $cartShippingApiModel */
        $cartShippingApiModel = Mage::getModel('checkout/cart_shipping_api');
        $shippingMethods      = $cartShippingApiModel->getShippingMethodsList($quote->getId());
        
        /**
         * If no shipping methods are found.
         */
        if (count($shippingMethods) == 0) {
            $session->addError($this->__('Apple Pay payment failed, because no shipping methods were found for the selected address. Please select a different shipping address within the pop-up or within your Apple Pay Wallet.'));
            throw new Exception();
        }
        
        foreach ($shippingMethods as $index => $shippingMethod) {
            $shippingMethods[$index]['price']              = round($shippingMethod['price'], 2);
            $shippingMethods[$index]['method_description'] = $shippingMethod['method_description'] ?: '';
            
            if ($shippingMethod['code'] == $address->getShippingMethod() && $index != 0) {
                $selectedIndex    = $index;
                $selectedShipping = $shippingMethods[$index];
            }
        }
        
        if (isset($selectedIndex) && $selectedIndex > 0) {
            unset($shippingMethods[$selectedIndex]);
            array_unshift($shippingMethods, $selectedShipping);
        }
        
        $this->setShippingMethodAction($shippingMethods[0]['code']);
        
        /**
         * Reload quote, because we've modified it in setShippingMethodAction().
         *
         * @var Mage_Checkout_Model_Session $quote
         */
        $quote            = Mage::getModel('checkout/session')->getQuote();
        $totals           = $process->gatherTotals($quote->getShippingAddress(), $quote->getTotals());
        $methodsAndTotals = $shippingMethods + $totals;
        
        /** @var Mage_Core_Helper_Data $coreHelper $coreHelper */
        $coreHelper = Mage::helper('core');
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody($coreHelper->jsonEncode($methodsAndTotals));
    }
    
    /**
     * Set Shipping Method if only one is available. [Apple Pay]
     *
     * @param null $identifier
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function setShippingMethodAction($identifier = null)
    {
        /** @var TIG_Buckaroo3Extended_Model_PaymentMethods_Applepay_Process $process */
        $process  = Mage::getModel(' buckaroo3extended/paymentMethods_applepay_process');
        $postData = Mage::app()->getRequest()->getPost() ?: $process->sanitizeParams($_GET);
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getModel('checkout/session');
        $quote   = $session->getQuote();
        
        $method = isset($postData['method']) ? $postData['method'] : $postData['wallet']['identifier'];
        if ($identifier !== null) {
            $method = $identifier;
        }
        /** @var Mage_Checkout_Model_Cart_Shipping_Api $cartShippingApiModel */
        $cartShippingApiModel = Mage::getModel('checkout/cart_shipping_api');
        $cartShippingApiModel->setShippingMethod($quote->getId(), $method);
        
        return true;
    }
    
    /**
     * Triggered when a different shipping method is selected.
     * Used by Apple Pay.
     *
     * @throws \Mage_Core_Model_Store_Exception
     */
    public function updateShippingMethodsAction()
    {
        /** @var TIG_Buckaroo3Extended_Model_PaymentMethods_Applepay_Process $process */
        $process  = Mage::getModel(' buckaroo3extended/paymentMethods_applepay_process');
        $postData = Mage::app()->getRequest()->getPost() ?: $process->sanitizeParams($_GET);
        $wallet   = array();
        if ($postData['wallet']) {
            $wallet = $postData['wallet'];
        }
        
        $this->setShippingMethodAction();
        
        /** @var Mage_Sales_Model_Quote $quote */
        $quote   = Mage::getModel('checkout/session')->getQuote();
        $address = $quote->getShippingAddress();
        
        $updateData          = $process->gatherTotals($address, $quote->getTotals());
        $updateData[0]->code = $wallet['identifier'];
        
        /** @var Mage_Core_Helper_Data $coreHelper $coreHelper */
        $coreHelper = Mage::helper('core');
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody($coreHelper->jsonEncode($updateData));
    }
    
    /**
     * Save Order [used in cart by Apple Pay]
     *
     * @throws \Mage_Core_Exception
     * @throws \Mage_Core_Model_Store_Exception
     */
    public function saveOrderAction()
    {
        /** @var Mage_Checkout_Model_Session $session */
        $session  = Mage::getModel('checkout/session');
        $quote    = $session->getQuote();
        $postData = Mage::app()->getRequest()->getPost();
        
        if (!$postData['payment']) {
            return;
        }
        
        if (!$postData['isCheckout']) {
            $process = Mage::getModel(' buckaroo3extended/paymentMethods_applepay_process');
            
            $shippingData          = $postData['payment']['shippingContact'];
            $walletShippingAddress = $process->processAddressFromWallet($shippingData, 'shipping');
            $billingData           = $postData['payment']['billingContact'];
            $walletBillingAddress  = $process->processAddressFromWallet($billingData, 'billing');
            
            /** @var Mage_Sales_Model_Quote_Address $shippingAddress */
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->addData($walletShippingAddress);
            /** @var Mage_Sales_Model_Quote_Address $billingAddress */
            $billingAddress = $quote->getBillingAddress();
            $billingAddress->addData($walletBillingAddress);
            
            $customer = $quote->getCustomer();
            if (!$customer->getId()) {
                $quote->setCheckoutMethod('guest')
                      ->setCustomerId(null)
                      ->setCustomerEmail($quote->getShippingAddress()->getEmail())
                      ->setCustomerIsGuest(true)
                      ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
            }
            
            $quote->getPayment()->importData(array('method' => 'buckaroo3extended_applepay'));
            $quote->setCurrency(Mage::app()->getStore()->getBaseCurrencyCode());
            $quote->collectTotals();
            $quote->save();
        }
        
        try {
            /** @var Mage_Sales_Model_Service_Quote $service */
            $service = Mage::getModel('sales/service_quote', $quote);
            $order   = $service->submitOrder();
            $order->save();
            
            /** @var TIG_Buckaroo3Extended_Model_Request_Abstract $request */
            $request = Mage::getModel('buckaroo3extended/request_abstract');
            $request->setOrder($order)->setOrderBillingInfo();
            $request->sendRequest();
        } catch (Exception $e) {
            $session->addError($this->__('Order could not be submitted: ') . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Triggered when order is successfully authorized and sent to Buckaroo.
     * Used by Apple Pay.
     *
     * @return \Mage_Core_Controller_Varien_Action
     */
    public function applepaySuccessAction()
    {
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getModel('checkout/session');
        $quote           = $checkoutSession->getQuote();
        /** @var Mage_Checkout_Model_Type_Onepage $checkoutSingleton */
        $checkoutSingleton = Mage::getModel('checkout/type_onepage');
        $session           = $checkoutSingleton->getCheckout();
        $orderCollection   = Mage::getModel('sales/order')->getCollection();
        $orderCollection->getSelect()->order('entity_id DESC')->limit('1');
        $lastItem    = $orderCollection->getLastItem();
        $orderId     = $lastItem->getEntityId();
        $incrementId = $lastItem->getIncrementId();
        
        $session->clearHelperData();
        $session->setLastSuccessQuoteId($quote->getId());
        $session->setLastQuoteId($quote->getId());
        $session->setLastOrderId($orderId);
        $session->setLastRealOrderId($incrementId);
        $session->setRedirectUrl('/checkout/onepage/success');
        
        Mage::getSingleton('checkout/cart')->truncate()->save();
        
        return $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }
    
    public function saveDataAction()
    {
        $data = $this->getRequest()->getPost();
        
        if (!is_array($data) || !isset($data['name']) || !isset($data['value'])
            || strpos($data['name'], 'buckaroo') === false
        ) {
            return;
        }
        
        $name  = $data['name'];
        $value = $data['value'];
        
        $session = Mage::getSingleton('checkout/session');
        $session->setData($name, $value);
    }
    
    public function pospaymentPendingAction()
    {
        $this->loadLayout();
        $this->getLayout();
        $this->renderLayout();
    }
    
    public function pospaymentCheckStateAction()
    {
        $response = array(
            'status'    => 'new',
            'returnUrl' => null
        );
        
        /** @var TIG_Buckaroo3Extended_Model_Response_Abstract $responseHandler */
        $responseHandler = Mage::getModel('buckaroo3extended/response_abstract');
        
        /** @var Mage_Sales_Model_Order $order */
        $order              = $responseHandler->getOrder();
        $response['status'] = $order->getState();
        
        switch ($response['status']) {
            case 'processing':
                $responseHandler->emptyCart();
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('buckaroo3extended')->__('Your order has been placed succesfully.')
                );
                $response['returnUrl'] = $this->getSuccessUrl($order->getStoreId());
                break;
            case 'canceled':
                $responseHandler->restoreQuote();
                
                $config       = Mage::getStoreConfig($responseHandler::BUCK_RESPONSE_DEFAUL_MESSAGE, $order->getStoreId());
                $errorMessage = Mage::helper('buckaroo3extended')->__($config);
                Mage::getSingleton('core/session')->addError($errorMessage);
                
                $response['returnUrl'] = $this->getFailedUrl($order->getStoreId());
                break;
        }
        
        $jsonResponse = Mage::helper('core')->jsonEncode($response);
        
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json');;
        $this->getResponse()->setBody($jsonResponse);
    }
    
    /**
     * @param $storeId
     *
     * @return string
     */
    protected function getSuccessUrl($storeId)
    {
        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/success_redirect', $storeId);
        $succesUrl      = Mage::getUrl($returnLocation, array('_secure' => true));
        
        return $succesUrl;
    }
    
    /**
     * @param $storeId
     *
     * @return string
     */
    protected function getFailedUrl($storeId)
    {
        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/failure_redirect', $storeId);
        $failedUrl      = Mage::getUrl($returnLocation, array('_secure' => true));
        
        return $failedUrl;
    }
}
