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
 */

class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Giftcards_Process extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{

    public function restoreQuote()
    {
        $quoteId = Mage::getSingleton('checkout/session')->getOldQuoteId();

        $quote = Mage::getModel('sales/quote')
            ->load($quoteId)
            ->setIsActive(true)
            ->setReservedOrderId(null)
            ->save();
        Mage::getSingleton('checkout/session')->replaceQuote($quote);
    }

    public static function stringRandom($length = 16)
    {
        $chars = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
        $str = "";

        for ($i=0; $i < $length; $i++)
        {
            $key = array_rand($chars);
            $str .= $chars[$key];
        }

        return $str;
    }

    public static function getQuoteBaseGrandTotal()
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
        $quoteData = $quote->getData();
        return $quoteData['base_grand_total'];
    }

    public static function getAlreadyPaid($orderId = false)
    {
        if(!$orderId){
            $quote = Mage::getModel('checkout/session')->getQuote();
            if($reservedOrderId = $quote->getReservedOrderId()){
                $orderId = $reservedOrderId;
            }
        }

        if($orderId){
            if($order = Mage::getModel('sales/order')->loadByIncrementId($orderId)){
                if($alreadyPaid = $order->getBaseBuckarooAlreadyPaid()){
                    return $alreadyPaid;
                }
            }
        }

        $alreadyPaid = Mage::getSingleton('core/session')->getBuckarooAlreadyPaid();
        return $alreadyPaid[$orderId] ? $alreadyPaid[$orderId] : false;
    }

    public static function setAlreadyPaid($orderId, $amount)
    {
        if($orderId){
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $quote->setBaseBuckarooAlreadyPaid($amount);
            $store = $quote->getStore();
            $quote->setBuckarooAlreadyPaid($store->convertPrice($alreadyPaid));
        }

        $alreadyPaid = Mage::getSingleton('core/session')->getBuckarooAlreadyPaid();
        $alreadyPaid[$orderId] = $amount;
        Mage::getSingleton('core/session')->setBuckarooAlreadyPaid($alreadyPaid);
    }

    public static function getOriginalTransactionKey($orderId)
    {
        $originalTransactionKey = Mage::getSingleton('core/session')->getOriginalTransactionKey();
        return $originalTransactionKey[$orderId] ? $originalTransactionKey[$orderId] : false;
    }

    public static function setOriginalTransactionKey($orderId, $transactionKey)
    {
        $originalTransactionKey = Mage::getSingleton('core/session')->getOriginalTransactionKey();
        $originalTransactionKey[$orderId] = $transactionKey;
        Mage::getSingleton('core/session')->setOriginalTransactionKey($originalTransactionKey);
    }

}
