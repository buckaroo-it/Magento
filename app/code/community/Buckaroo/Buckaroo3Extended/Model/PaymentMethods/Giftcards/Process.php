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

    public function sendRequest($data)
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
        $quoteData = $quote->getData();
        if(!$quote->getReservedOrderId()){
            $quote->reserveOrderId()->save();
        }

        $storeId = $quoteData['store_id'];
        $websiteKey = Mage::getStoreConfig('buckaroo/buckaroo3extended/key', $storeId);
        $secretKey = Mage::getStoreConfig('buckaroo/buckaroo3extended/digital_signature', $storeId);
        $test = Mage::getStoreConfig('buckaroo/buckaroo3extended/mode', $storeId);
        if (!$test && Mage::getStoreConfig('buckaroo/buckaroo3extended' . $this->_code . '/mode', $storeId)) {
            $test = '1';
        }
        $currency = $quoteData['base_currency_code'];
        $oldQuoteId = Mage::getModel('checkout/session')->getQuote()->getId();
        Mage::getSingleton('checkout/session')->setOldQuoteId($oldQuoteId);
        $orderId = $quote->getReservedOrderId();
        $currentgiftcard = $data['giftcard'];

        $returnLocation = Mage::getStoreConfig('buckaroo3extended/notify/return', $storeId);
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        switch ($currentgiftcard) {
            case 'fashioncheque':
                $parameters = [
                    'number' => 'FashionChequeCardNumber',
                    'pin' => 'FashionChequePin',
                ];
                break;
            case 'tcs':
                $parameters = [
                    'number' => 'TCSCardnumber',
                    'pin' => 'TCSValidationCode',
                ];
                break;
            default:
                $parameters = [
                    'number' => 'IntersolveCardnumber',
                    'pin' => 'IntersolvePin',
                ];
        }

        $grandTotal = $quoteData['base_grand_total'];

        $postArray = array(
            "Currency" => $currency,
            "AmountDebit" => $grandTotal,
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
                                "Value" => $data['cardNumber']
                            ),array(
                                "Name" => $parameters['pin'],
                                "Value" => $data['pin']
                            )
                        )
                    )
                )
            )
        );

        if($originalTransactionKey = $this->getOriginalTransactionKey($orderId)){
            $postArray['Services']['ServiceList'][0]['Action'] = 'PayRemainder';
            $postArray['OriginalTransactionKey'] = $originalTransactionKey;
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
        $response = json_decode($result, true);

        $res['status'] = $response['Status']['Code']['Code'];
        $orderId = $response['Invoice'];
        if($response['Status']['Code']['Code']=='190'){
            $res['RemainderAmount'] = $response['RequiredAction']['PayRemainderDetails']['RemainderAmount'];
            $alreadyPaid = $this->getAlreadyPaid($orderId) + $response['AmountDebit'];
            
            if($response['RequiredAction']['PayRemainderDetails']['RemainderAmount']>0){
                $this->setOriginalTransactionKey($orderId, $response['RequiredAction']['PayRemainderDetails']['GroupTransaction']);
            }

            if($response['RequiredAction']['PayRemainderDetails']['RemainderAmount']>0){
                $message = "A partial payment of ".$response['Currency']." ".$response['AmountDebit']." was successfully performed on a requested amount. Remainder amount ".$response['RequiredAction']['PayRemainderDetails']['RemainderAmount']." ".$response['RequiredAction']['PayRemainderDetails']['Currency'];
            }else{
                $message = "Your payed succesfully. Please finish your order";
            }
            $this->setAlreadyPaid($orderId, $alreadyPaid);
            $res['alreadyPaid'] = $alreadyPaid;
            $res['message'] = Mage::helper('buckaroo3extended')->__($message);
        }else{
            $res['error'] = $response['Status']['SubCode']['Description'];
        }
        return $res;
    }

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

    public static function getAlreadyPaid($orderId= false)
    {
        if(!$orderId){
            $quote = Mage::getModel('checkout/session')->getQuote();
            if($reservedOrderId = $quote->getReservedOrderId()){
                $orderId = $reservedOrderId;
            }
        }
        $alreadyPaid = Mage::getSingleton('core/session')->getAlreadyPaid();
        return $alreadyPaid[$orderId] ? $alreadyPaid[$orderId] : false;
    }

    public static function setAlreadyPaid($orderId, $amount)
    {
        $alreadyPaid = Mage::getSingleton('core/session')->getAlreadyPaid();
        $alreadyPaid[$orderId] = $amount;
        Mage::getSingleton('core/session')->setAlreadyPaid($alreadyPaid);
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
