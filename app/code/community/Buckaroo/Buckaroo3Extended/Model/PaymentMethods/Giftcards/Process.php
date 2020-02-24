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

    public function sendRequest(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();
        $payment = $order->getPayment();
        $currentgiftcard = $payment->getAdditionalInformation('currentgiftcard');

        $websiteKey = Mage::getStoreConfig('buckaroo/buckaroo3extended/key', $order->getStoreId());
        $secretKey = Mage::getStoreConfig('buckaroo/buckaroo3extended/digital_signature', $order->getStoreId());
        $test = Mage::getStoreConfig('buckaroo/buckaroo3extended/mode', $order->getStoreId());
        if (!$test && Mage::getStoreConfig('buckaroo/buckaroo3extended' . $this->_code . '/mode', $order->getStoreId())) {
            $test = '1';
        }
        $currency = $order->getBaseCurrency()->getCode();
        $orderId = Mage::getSingleton('core/session')->getPartOrderId() ? Mage::getSingleton('core/session')->getPartOrderId() : $order->getIncrementId();
        Mage::getSingleton('core/session')->setPartOrderId(null);

        $oldQuoteId = Mage::getModel('checkout/session')->getQuote()->getId();
        Mage::getSingleton('checkout/session')->setOldQuoteId($oldQuoteId);

        // $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/success_redirect', $this->_order->getStoreId());
        $returnLocation = Mage::getStoreConfig('buckaroo3extended/notify/return', $order->getStoreId());
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        if($currentgiftcard == 'fashioncheque'){
            $parameters = [
                'number' => 'FashionChequeCardNumber',
                'pin' => 'FashionChequePin',
            ];
        }elseif($currentgiftcard == 'tcs'){
            $parameters = [
                'number' => 'TCSCardnumber',
                'pin' => 'TCSValidationCode',
            ];
        }else{
            $parameters = [
                'number' => 'IntersolveCardnumber',
                'pin' => 'IntersolvePin',
            ];
        }

        $postArray = array(
            "Currency" => $currency,
            "AmountDebit" => $order->getGrandTotal(),
            "Invoice" => $orderId,
            "ReturnURL" => $returnUrl,
            "Services" => array(
                "ServiceList" => array(
                    array(
                        "Action" => "Pay",
                        "Name" => $currentgiftcard,
                        "Parameters" => array(
                            array(
                                "Name" => $parameters['number'],
                                "Value" => $payment->getAdditionalInformation('IntersolveCardnumber')
                            ),array(
                                "Name" => $parameters['pin'],
                                "Value" => $payment->getAdditionalInformation('IntersolvePin')
                            )
                        )
                    )
                )
            )
        );
        if($originalTransactionKey = Mage::getSingleton('core/session')->getOriginalTransactionKey()){
            Mage::getSingleton('core/session')->setOriginalTransactionKey(null);
            $postArray['Services']['ServiceList'][0]['Action'] = 'PayRemainder';
            $postArray['OriginalTransactionKey'] = $originalTransactionKey;
            Mage::getSingleton('checkout/session')->setOldOrderIdToRemove($order->getIncrementId());
        }

        $url = ($test == 1) ? 'testcheckout.buckaroo.nl' : 'checkout.buckaroo.nl';
        $uri        = 'https://'.$url.'/json/Transaction';
        $uri2       = strtolower(rawurlencode($url.'/json/Transaction'));

        $timeStamp = time();
        $httpMethod = 'POST';
        $nonce      = $this->stringRandom();

        $json = json_encode($postArray, JSON_PRETTY_PRINT);
        $md5 = md5($json, true);
        $encodedContent = base64_encode($md5);

        $rawData = $websiteKey . $httpMethod . $uri2 . $timeStamp . $nonce . $encodedContent;
        $hash = hash_hmac('sha256', $rawData, $secretKey, true);
        $hmac = base64_encode($hash);

        $hmac_full = $websiteKey . ':' . $hmac . ':' . $nonce . ':' . $timeStamp;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Magento1');
        curl_setopt($curl, CURLOPT_URL, $uri);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $httpMethod);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
            'Authorization: hmac ' . $hmac_full,
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);
        $curlInfo = curl_getinfo($curl);
        $decodedResult = json_decode($result, true);

        if($decodedResult['Status']['Code']['Code']=='190'){
            $this->partialPaymentSave($decodedResult, $observer);
            return $this;
        }
    }

    protected function partialPaymentSave($response, Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();
        
        if($response['RequiredAction']['PayRemainderDetails']['RemainderAmount']>0){
            $message = "A partial payment of ".$response['Currency']." ".$response['AmountDebit']." was successfully performed on a requested amount. Remainder amount ".$response['RequiredAction']['PayRemainderDetails']['RemainderAmount']." ".$response['RequiredAction']['PayRemainderDetails']['Currency'];
            Mage::getSingleton('core/session')->setPartOrderId($response['Invoice']);
            Mage::getSingleton('core/session')->setOriginalTransactionKey($response['RequiredAction']['PayRemainderDetails']['GroupTransaction']);

            Mage::getSingleton('core/session')->setAlreadyPaid(Mage::getSingleton('core/session')->getAlreadyPaid() + $response['AmountDebit']);
 
            $this->restoreQuote($order);

        }else{
            Mage::getSingleton('core/session')->setAlreadyPaid(null);
            $message = "Your order has been placed succesfully.";
        }

        $helper = Mage::helper('buckaroo3extended');

        $order->addStatusHistoryComment(
            $helper->__($message)
        );
        
        $order->save();

        Mage::getSingleton('core/session')->addSuccess(
            $helper->__($message)
        );

        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/' . ($response['RequiredAction']['PayRemainderDetails']['RemainderAmount'] > 0 ? 'failure_redirect' : 'success_redirect'), $order->getStoreId());
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        if($remove_order_id = Mage::getSingleton('checkout/session')->getOldOrderIdToRemove()){
            Mage::register('isSecureArea', true);
            Mage::getModel('sales/order')->loadByIncrementId($remove_order_id)->delete();
            Mage::unregister('isSecureArea');
            Mage::getSingleton('checkout/session')->setOldOrderIdToRemove(null);
        }

        Mage::app()->getResponse()->setRedirect($returnUrl)->sendResponse();
        die();
    }

    public function restoreQuote($order)
    {
        $quoteId = $order->getQuoteId();

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
}
