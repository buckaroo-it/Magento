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
class TIG_Buckaroo3Extended_Block_PaymentMethods_Payconiq_Checkout_Pay extends Mage_Core_Block_Template
{
    /** @var string */
    protected $_transactionKey;

    public function _construct()
    {
        parent::_construct();

        $session = Mage::getSingleton('checkout/session');
        $orderId = $session->getLastRealOrderId();

        /** @var Mage_Sales_Model_Order $order */
        $order                 = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $this->_transactionKey = $order->getTransactionKey();
    }

    /**
     * @return string
     */
    public function getTransactionKey()
    {
        return $this->_transactionKey;
    }

    /**
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('buckaroo3extended/payconiq/cancel', array('_secure'=>true));
    }

    /**
     * @return string
     */
    public function getCancelMessage()
    {
        $helper = Mage::helper('buckaroo3extended');
        $message = 'You have canceled the order. '
            . 'We kindly ask you to not complete the payment in the Payconiq app - Your order will not be processed. '
            . 'Place the order again if you still want to make the payment.';
        $translatedMessage = $helper->__($message);

        return $translatedMessage;
    }
}
