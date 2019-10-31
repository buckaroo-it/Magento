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
class TIG_Buckaroo3Extended_Model_Observer_CancelAuthorize extends Mage_Core_Model_Abstract
{
    /** @var array */
    protected $_allowedMethods = array('afterpay', 'afterpay2', 'afterpay20', 'klarna');

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function sales_order_payment_void_authorize(Varien_Event_Observer $observer)
    {
        //file_put_contents('/tmp/vlad', "+++++++++sales_order_payment_void_authorize", FILE_APPEND);
        return $this->sales_order_payment_cancel_authorize($observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function sales_order_payment_cancel_authorize(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getPayment();

        // Do not cancel authorize when accept authorize is failed.
        // buckaroo_failed_authorize is set in Push.php
        if ($payment->getAdditionalInformation('buckaroo_failed_authorize') == 1) {
            return $this;
        }

        // Only allow when pushed in the backend on the cancel or void buttons
        if (
            isset($_SERVER['PATH_INFO'])
            &&
            (strpos($_SERVER['PATH_INFO'], 'sales_order/cancel') === false)
            &&
            (strpos($_SERVER['PATH_INFO'], 'sales_order/voidPayment') === false)
        ) {
            return $this;
        }

        $paymentMethodAction = $payment->getMethodInstance()->getConfigPaymentAction();

        /** The first characters are "buckaroo3extended_" which are the same for all methods.
            Therefore we don't need to validate this part. */
        $paymentMethodCode = substr($payment->getMethodInstance()->getCode(), 18);

        if (in_array($paymentMethodCode, $this->_allowedMethods)
            && $paymentMethodAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE
            && !$payment->getSkipCancelAuthorize()
        ) {
            if (($paymentMethodCode == 'afterpay20') && $paymentMethodAction == 'authorize') {
                $voidTransactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addAttributeToFilter('order_id', array('eq' => $payment->getOrder()->getEntityId()))
                    ->addAttributeToFilter('txn_type', array('eq' => 'void'));

                if (!empty($voidTransactions) && sizeof($voidTransactions)>0) {
                    return false;
                }
            }

            /** @var TIG_Buckaroo3Extended_Model_Request_CancelAuthorize $cancelAuthorizeRequest */
            $cancelAuthorizeRequest = Mage::getModel(
                'buckaroo3extended/request_cancelAuthorize',
                array(
                    'payment' => $payment
                )
            );

            try {
                $cancelAuthorizeRequest->sendRequest();
            } catch (Exception $e) {
                Mage::helper('buckaroo3extended')->logException($e);
                Mage::throwException($e->getMessage());
            }
        }

        return $this;
    }
}
