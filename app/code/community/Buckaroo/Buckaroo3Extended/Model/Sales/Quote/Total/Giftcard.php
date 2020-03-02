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
class Buckaroo_Buckaroo3Extended_Model_Sales_Quote_Total_Giftcard
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    /**
     * The code of this 'total'.
     *
     * @var string
     */
    protected $_totalCode = 'buckaroo_already_paid';

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        /**
         * We can only add the fee to the shipping address.
         */
        if ($address->getAddressType() != 'shipping') {
            return $this;
        }

        $quote = $address->getQuote();
        $store = $quote->getStore();

        if (!$quote->getId()) {
            return $this;
        }

        $items = $address->getAllItems();
        if (empty($items)) {
            return $this;
        }

        /**
         * First, reset the fee amounts to 0 for this address and the quote.
         */
        $address->setBuckarooAlreadyPaid(0)
                ->setBaseBuckarooAlreadyPaid(0);
        $quote->setBuckarooAlreadyPaid(0)
                ->setBaseBuckarooAlreadyPaid(0);

        /**
         * Check if the order was placed using Buckaroo
         */
        $paymentMethod = $quote->getPayment()->getMethod();

        if($reservedOrderId = $quote->getReservedOrderId()){
            $process = Mage::getModel('buckaroo3extended/paymentMethods_giftcards_process');
            if($baseAlreadyPaid = $process->getAlreadyPaid($reservedOrderId)){
                $alreadyPaid = $store->convertPrice($baseAlreadyPaid);
                $address->setBuckarooAlreadyPaid($alreadyPaid)
                        ->setBaseBuckarooAlreadyPaid($baseAlreadyPaid);
                $quote->setBuckarooAlreadyPaid($alreadyPaid)
                      ->setBaseBuckarooAlreadyPaid($baseAlreadyPaid);
                if($paymentMethod != 'buckaroo3extended_giftcards'){
                    $address->setBaseGrandTotal($address->getBaseGrandTotal() - $baseAlreadyPaid);
                    $address->setGrandTotal($address->getGrandTotal() - $store->convertPrice($alreadyPaid));
                }
            }
        }

        if (strpos($paymentMethod, 'buckaroo') === false) {
            return $this;
        }

        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getBuckarooAlreadyPaid();

        if ($amount <= 0) {
            return $this;
        }

        $helper = Mage::helper('buckaroo3extended');

        $address->addTotal(
            array(
                'code'  => $this->getCode(),
                'title' => $helper->__("Paid with Giftcard"),
                'value' => $amount,
            )
        );

        return $this;
    }
}
