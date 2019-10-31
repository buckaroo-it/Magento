<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Applepay_Cart_Button
    extends Mage_Adminhtml_Block_Abstract
{
    /** @var bool $isProductPage */
    protected $isProductPage = false;

    /**
     * TIG_Buckaroo3Extended_Block_PaymentMethods_Applepay_Cart_Button constructor.
     */
    public function _construct()
    {
        parent::_construct();
    }
    
    public function isLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }
    
    /**
     * @return mixed
     */
    public function isConfigurable()
    {
        return false;
    }

    /**
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getCurrency()
    {
        return Mage::app()->getStore()->getCurrentCurrencyCode();
    }
    
    /**
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getCountryCode()
    {
        $store   = Mage::app()->getStore();
        $storeId = $store->getId();
        return Mage::getStoreConfig('general/country/default', $storeId) ?: 'US';
    }

    /**
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getStoreName()
    {
        return Mage::app()->getStore()->getFrontendName();
    }

    /**
     * @return string
     */
    public function getSubtotalText()
    {
        $config = Mage::getStoreConfig('tax/cart_display/subtotal');

        if ($config == 1) {
            return $this->__('Subtotal excl. tax');
        }

        return $this->__('Subtotal');
    }
    
    /**
     * @return float
     */
    public function getSubtotal()
    {
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = Mage::getModel('checkout/cart');
        
        return round($cart->getQuote()->getSubtotalWithDiscount(), 2);
    }
    
    /**
     * @return float
     */
    public function getGrandTotal()
    {
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = Mage::getModel('checkout/cart');
        
        return round($cart->getQuote()->getGrandTotal(), 2);
    }

    /**
     * @return mixed
     */
    public function getCultureCode()
    {
        $localeCode   = Mage::app()->getLocale()->getLocaleCode();
        $shortLocale  = explode('_', $localeCode)[0];

        return $shortLocale;
    }
    
    /**
     * @return mixed
     */
    public function getGuid()
    {
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart  = Mage::getModel('checkout/cart');
        $quote = $cart->getQuote();
        $store = $quote->getStore();
        $guid  = Mage::getStoreConfig('buckaroo/buckaroo3extended/guid', $store->getId());

        return $guid;
    }

    /**
     * Is overwritten in the Block/Product/Button.php to see if we're on the product page or the shopping cart page.
     *
     * @return bool
     */
    public function setProductPage()
    {
        return $this->isProductPage = false;
    }

    /**
     * @return string
     */
    public function getControllerUrl()
    {
        $this->setProductPage();

        if ($this->isProductPage) {
            return $this->getUrl('buckaroo3extended/checkout/addToCart');
        }

        return $this->getUrl('buckaroo3extended/checkout/loadShippingMethods');
    }

    /**
     * @return string
     */
    public function getUpdateShippingMethodsUrl()
    {
        return $this->getUrl('buckaroo3extended/checkout/updateShippingMethods');
    }

    /**
     * @return string
     */
    public function getSetShippingMethodUrl()
    {
        return $this->getUrl('buckaroo3extended/checkout/setShippingMethod');
    }

    /**
     * @return string
     */
    public function getSaveOrderUrl()
    {
        return $this->getUrl('buckaroo3extended/checkout/saveOrder');
    }

    /**
     * @return string
     */
    public function getApplepaySuccessUrl()
    {
        return $this->getUrl('buckaroo3extended/checkout/applepaySuccess');
    }
}
