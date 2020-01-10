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
class TIG_Buckaroo3Extended_PayconiqController extends Mage_Core_Controller_Front_Action
{
    /** @var null|Mage_Sales_Model_Order */
    protected $_order = null;

    public function checkoutAction()
    {
        /** @var TIG_Buckaroo3Extended_Model_Request_Abstract $request */
        $request = Mage::getModel('buckaroo3extended/request_abstract');
        $request->setResponseModelClass('buckaroo3extended/response_payconiq');
        $request->sendRequest();
    }

    public function payAction()
    {
        if (!$this->canShowPage()) {
            return;
        }

        $this->loadLayout();
        $this->getLayout();
        $this->renderLayout();
    }

    /**
     * @throws Mage_Core_Exception
     */
    public function cancelAction()
    {
        if (!$this->canShowPage()) {
            return;
        }

        $this->sendCancelRequest();
        $this->updateStatusHistory();
        $this->restoreQuote();
        $this->addErrorMessage();
        $this->cancelOrder();

        $storeId = $this->getOrder()->getStoreId();
        $url = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/failure_redirect', $storeId);
        $this->_redirect($url);
    }

    /**
     * @return bool
     */
    protected function canShowPage()
    {
        $session = Mage::getSingleton('checkout/session');

        if (!$session->getLastSuccessQuoteId() || !$session->getLastRealOrderId()) {
            $this->_forward('defaultNoRoute');
            return false;
        }

        return true;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder()
    {
        if ($this->_order != null) {
            return $this->_order;
        }

        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        return $this->_order;
    }

    /**
     * @throws Mage_Core_Exception
     */
    protected function sendCancelRequest()
    {
        $payment = $this->getOrder()->getPayment();

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment->setAdditionalInformation('skip_push', 1);
        $payment->save();

        /** @var TIG_Buckaroo3Extended_Model_Request_CancelAuthorize $cancelRequest */
        $cancelRequest = Mage::getModel('buckaroo3extended/request_cancelAuthorize', array('payment' => $payment));

        try {
            $cancelRequest->sendRequest();
        } catch (Exception $e) {
            Mage::helper('buckaroo3extended')->logException($e);
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * @throws Mage_Core_Exception
     */
    protected function updateStatusHistory()
    {
        $order = $this->getOrder();
        $comment = Mage::helper('buckaroo3extended')->__('Your payment was unsuccessful, cancelled by consumer.');
        $order->addStatusHistoryComment($comment);

        try {
            $order->save();
        } catch (Exception $e) {
            Mage::helper('buckaroo3extended')->logException($e);
            Mage::throwException($e->getMessage());
        }
    }

    protected function restoreQuote()
    {
        $order = $this->getOrder();
        $quote = Mage::getModel('sales/quote')
            ->load($order->getQuoteId())
            ->setIsActive(true)
            ->setReservedOrderId(null)
            ->save();
        Mage::getSingleton('checkout/session')->replaceQuote($quote);
    }

    protected function addErrorMessage()
    {
        $order = $this->getOrder();
        $errorMessagePath = TIG_Buckaroo3Extended_Model_Response_Abstract::BUCK_RESPONSE_CANCELED_BY_USER;
        $errorMessageConfig = Mage::getStoreConfig($errorMessagePath, $order->getStoreId());
        $errorMessage = Mage::helper('buckaroo3extended')->__($errorMessageConfig);
        Mage::getSingleton('core/session')->addError($errorMessage);
    }

    /**
     * @throws Mage_Core_Exception
     */
    protected function cancelOrder()
    {
        $order = $this->getOrder();

        if (!Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/cancel_on_failed', $order->getStoreId())) {
            return;
        }

        if ((float)$order->getGiftCardsAmount() > 0) {
            $cards = Mage::helper('enterprise_giftcardaccount')->getCards($order);
            $this->refundGiftcards($cards);
        }

        try {
            $order->cancel()->save();
        } catch (Exception $e) {
            Mage::helper('buckaroo3extended')->logException($e);
            Mage::throwException($e->getMessage());
        }
    }

    /**
     * @param array $cards
     */
    protected function refundGiftcards($cards)
    {
        if (!is_array($cards)) {
            return;
        }

        foreach ($cards as $card) {
            $this->revertGiftcard($card);
        }
    }

    /**
     * @param array $card
     */
    protected function revertGiftcard($card)
    {
        if (!isset($card['authorized'])) {
            return;
        }

        $giftCard = Mage::getModel('enterprise_giftcardaccount/giftcardaccount')->load($card['i']);

        if (!$giftCard) {
            return;
        }

        $giftCard->revert($card['authorized'])->unsOrder()->save();
    }
}
