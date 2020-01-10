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
class TIG_Buckaroo3Extended_Model_Observer_CancelAuthorize extends Mage_Core_Model_Abstract
{
    /** @var array */
    protected $_allowedMethods = array('afterpay', 'afterpay2', 'klarna');

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

        // Only allow when pushed in the backend on the cancel button
        if (isset($_SERVER['PATH_INFO']) && strpos($_SERVER['PATH_INFO'], 'sales_order/cancel') === false) {
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
