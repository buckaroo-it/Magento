<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Applepay_Product_Button
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Applepay_Cart_Button
{
    /**
     * @return mixed
     */
    public function getProductId()
    {
        return Mage::registry('current_product')->getId();
    }

    /**
     * Overwrites Block/Cart/Button.php to see if we're on the product page or the shopping cart page.
     *
     * @return bool
     */
    public function setProductPage()
    {
        return $this->isProductPage = true;
    }
    
    /**
     * @return mixed
     */
    public function isConfigurable()
    {
        return (Mage::registry('current_product')->getTypeId() === 'configurable');
    }
}
