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
class TIG_Buckaroo3Extended_Model_PaymentMethods_Masterpass_Observer
    extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    protected $_code = 'buckaroo3extended_masterpass';
    protected $_method = 'masterpass';

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        /**
         * @var Mage_Core_Controller_Request_Http $request
         */
        $request = $observer->getRequest();

        $vars = $request->getVars();
        $serviceVersion = $this->_getServiceVersion();
        $array = array(
            $this->_method     => array(
                'action'    => 'PaymentInvitation',
                'version'   => $serviceVersion,
            ),
        );

        /**
         * @var Mage_Sales_Model_Order $order
         */
        $order = $request->getOrder();
        $order->setBuckarooServiceVersionUsed($serviceVersion)
            ->save();

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

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request            = $observer->getRequest();
        $this->_billingInfo = $request->getBillingInfo();
        $this->_order       = $request->getOrder();

        $vars = $request->getVars();

        if (Mage::getStoreConfig('buckaroo/buckaroo3extended_' . $this->_method . '/use_creditmanagement', Mage::app()->getStore()->getStoreId())) {
            $this->_addCustomerVariables($vars);
            $this->_addCreditManagement($vars);
            $this->_addAdditionalCreditManagementVariables($vars);
        }

        $shippingCosts = round($this->_order->getBaseShippingInclTax(), 2);

        $discount = null;

        if(Mage::helper('buckaroo3extended')->isEnterprise()){
            if((double)$this->_order->getGiftCardsAmount() > 0){
                $discount = (double)$this->_order->getGiftCardsAmount();
            }
        }

        if(abs((double)$this->_order->getDiscountAmount()) > 0){
            $discount += abs((double)$this->_order->getDiscountAmount());
        }

        $array = array(
            'Discount'              => $discount,
            'ShippingCosts'         => $shippingCosts,
            'ShippingSuppression'   => 'TRUE',
        );

        $products = $this->_order->getAllItems();
        $group    = array();
        foreach($products as $item){
            /** @var Mage_Sales_Model_Order_Item $item */
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
            $article['ArticleQuantity']['value']    = 1;
            $article['ArticleUnitPrice']['value']   = (string) $productPrice;

            $group[] = $article;
        }

        $paymentFeeArray = $this->_getPaymentFeeLine();
        if(false !== $paymentFeeArray && is_array($paymentFeeArray)){
            $group[] = $paymentFeeArray;
        }

        $array['Articles'] = $group;

        // fallback if the request is based on a quote
        if($this->_order instanceof Mage_Sales_Model_Quote)
        {
            $quote = $this->_order;

            // repair empty order ID
            $vars['orderId'] = 'quote_' . $quote->getId();

            // repair empty discount price
            $discount = $quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount();
            $array['Discount'] = (double) $discount;

            // repair empty article prices
            $products = $quote->getAllItems();
            $groupId  = 0;
            foreach($products as $item)
            {
                if (empty($item) || $item->hasParentItemId()) {
                    continue;
                }

                $productPrice = ($item->getBasePrice() * $item->getQty()) + $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();

                if($item->getProductType() == 'bundle') {
                    $productPrice = $quote->getSubtotal() + $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount();
                }

                $array['Articles'][$groupId]['ArticleDescription']['value'] = (int) $item->getQty() . 'x ' . $item->getName();
                $array['Articles'][$groupId]['ArticleUnitPrice']['value']   = (string) round($productPrice, 2);

                $groupId++;
            }

            // repair empty shipping costs
            $array['ShippingCosts'] = (string) round($vars['amountDebit'] - $quote->getBaseSubtotalWithDiscount(), 2);

            // enable remote shipping selection
            $array['ShippingSuppression'] = 'FALSE';
        }

        if (array_key_exists('customVars', $vars) && array_key_exists($this->_method, $vars['customVars']) && is_array($vars['customVars'][$this->_method])) {
            $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $array);
        } else {
            $vars['customVars'][$this->_method] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    /**
     * @return bool
     */
    protected function _getPaymentFeeLine()
    {
        $fee        = (float) $this->_order->getBuckarooFee();
        $feeTax     = (float) $this->_order->getBuckarooFeeTax();
        $feeTotal   = (float) $fee+$feeTax;

        if($fee > 0){
            $article['ArticleDescription']['value'] = 'Servicekosten';
            $article['ArticleQuantity']['value']    = 1;
            $article['ArticleUnitPrice']['value']   = (string) round($feeTotal, 2);
            return $article;
        }

        return false;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
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


}
