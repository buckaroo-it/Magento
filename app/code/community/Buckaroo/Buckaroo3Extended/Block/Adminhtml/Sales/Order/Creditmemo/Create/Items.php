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
class Buckaroo_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Creditmemo_Create_Items extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Create_Items {
    /**
     * Retrieve order totalbar block data
     *
     * @return array
     */
    public function getOrderTotalbarData()
    {
        $refundType = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_afterpay/refundtype',
            Mage::app()->getStore()->getStoreId()
        );

        $totalbarData = parent::getOrderTotalbarData();

        if ($refundType == 'without') {
            $remainingAmount = $this->displayPrices(
                $this->getOrder()->getData('total_invoiced') - $this->getOrder()->getData('total_refunded'),
                $this->getOrder()->getData('total_invoiced') - $this->getOrder()->getData('total_refunded')
            );

            array_splice(
                $totalbarData,
                1,
                0,
                array(array(Mage::helper('sales')->__('Remaining Amount'), $remainingAmount, false))
            );
        }

        return $totalbarData;
    }
}
