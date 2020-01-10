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
class TIG_Buckaroo3Extended_Model_PaymentFee_Order_Creditmemo_Total_FeeTax
    extends TIG_Buckaroo3Extended_Model_PaymentFee_Order_Creditmemo_Total_Fee_Abstract
{
    /**
     * Get the Buckaroo Payment fee tax total amount.
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     *
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $feeTax     = $creditmemo->getBuckarooFeeTax();
        $baseFeeTax = $creditmemo->getBaseBuckarooFeeTax();

        /**
         * If a creditmemo already has a fee tax, we only need to update the totals.
         */
        if ($feeTax && $baseFeeTax) {
            $creditmemo->setBuckarooFeeTax($feeTax)
                       ->setBaseBuckarooFeeTax($baseFeeTax)
                       ->setTaxAmount($creditmemo->getTaxAmount() + $feeTax)
                       ->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $baseFeeTax)
                       ->setGrandTotal($creditmemo->getGrandTotal() + $feeTax)
                       ->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseFeeTax);

            $order->setBuckarooFeeTaxRefunded($order->getBuckarooFeeTaxRefunded() + $feeTax)
                  ->setBaseBuckarooFeeTaxRefunded($order->getBaseBuckarooFeeTaxRefunded() + $baseFeeTax);

            return $this;
        }

        /**
         * If the creditmemo has a fee, but no fee tax, we need to calculate the fee tax.
         */
        $fee     = $creditmemo->getBuckarooFee();
        $baseFee = $creditmemo->getBaseBuckarooFee();

        if ($fee && $baseFee) {
            /**
             * First we need to determine what percentage of the fee is being refunded. We need to refund the same
             * percentage of fee tax.
             */
            $totalBaseFee = $order->getBaseBuckarooFee();
            $ratio        = $baseFee / $totalBaseFee;

            /**
             * Calculate the fee and base fee tax based on the same ratio.
             */
            $totalBaseFeeTax = $order->getBaseBuckarooFeeTax();
            $baseFeeTax      = $totalBaseFeeTax * $ratio;

            $totalFeeTax = $order->getBuckarooFeeTax();
            $feeTax      = $totalFeeTax * $ratio;

            /**
             * If the total amount refunded exceeds the available fee tax amount, we have a rounding error. Modify the
             * fee tax amounts accordingly.
             */
            $totalBaseFeeTax = $baseFeeTax - $order->getBaseBuckarooFeeTax()
                                           - $order->getBaseBuckarooFeeTaxRefunded();
            if ($totalBaseFeeTax < 0.0001 && $totalBaseFeeTax > -0.0001) {
                $baseFeeTax = $order->getBaseBuckarooFeeTax() - $order->getBaseBuckarooFeeTaxRefunded();
            }

            $totalFeeTax = $feeTax - $order->getBuckarooFeeTax() - $order->getBuckarooFeeTaxRefunded();
            if ($totalFeeTax < 0.0001 && $totalFeeTax > -0.0001) {
                $feeTax = $order->getBuckarooFeeTax() - $order->getBuckarooFeeTaxRefunded();
            }

            /**
             * Update the creditmemo totals.
             */
            $creditmemo->setBuckarooFeeTax($feeTax)
                       ->setBaseBuckarooFeeTax($baseFeeTax)
                       ->setTaxAmount($creditmemo->getTaxAmount() + $feeTax)
                       ->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $baseFeeTax)
                       ->setGrandTotal($creditmemo->getGrandTotal() + $feeTax)
                       ->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseFeeTax);

            $order->setBuckarooFeeTaxRefunded($order->getBuckarooFeeTaxRefunded() + $feeTax)
                  ->setBaseBuckarooFeeTaxRefunded($order->getBaseBuckarooFeeTaxRefunded() + $baseFeeTax);

            return $this;
        }

        return $this;
    }
}
