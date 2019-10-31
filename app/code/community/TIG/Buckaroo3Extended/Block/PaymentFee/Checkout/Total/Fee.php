<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 *
 * @method Varien_Object getTotal()
 */
class TIG_Buckaroo3Extended_Block_PaymentFee_Checkout_Total_Fee extends Mage_Checkout_Block_Total_Default
{
    /**
     * Display modes for the Buckaroo Payment fee.
     */
    const DISPLAY_MODE_EXCL = 1;
    const DISPLAY_MODE_INCL = 2;
    const DISPLAY_MODE_BOTH = 3;

    /**
     * Xpath to the Buckaroo Payment fee display mode setting.
     */
    const XPATH_DISPLAY_MODE_BUCKAROO_FEE = 'tax/cart_display/buckaroo_fee';

    /**
     * @var string
     */
    protected $_template = 'buckaroo3extended/paymentFee/checkout/fee.phtml';

    /**
     * Get the display mode for the Buckaroo Payment fee.
     *
     * @return int
     */
    public function getDisplayMode()
    {
        $displayMode = (int) Mage::getStoreConfig(self::XPATH_DISPLAY_MODE_BUCKAROO_FEE, $this->_store);
        return $displayMode;
    }

    /**
     * Get the tax label for either incl. or excl. tax.
     *
     * @param boolean $inclTax
     *
     * @return string
     */
    public function getTaxLabel($inclTax = false)
    {
        $taxLabel = Mage::helper('tax')->getIncExcTaxLabel($inclTax);

        return $taxLabel;
    }

    /**
     * Get the Buckaroo Payment fee value incl or excl. tax.
     *
     * @param bool $inclTax
     *
     * @return bool
     */
    public function getValue($inclTax = false)
    {
        $address = $this->getTotal()->getAddress();

        $exclTax = $address->getBuckarooFee();
        if (!$inclTax) {
            return $exclTax;
        }

        $inclTax = $exclTax + $address->getBuckarooFeeTax();
        return $inclTax;
    }
}
