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
class TIG_Buckaroo3Extended_Model_Sources_Capture
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE,
                'label' => Mage::helper('buckaroo3extended')->__('Online')
            ),
            array(
                'value' => Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE,
                'label' => Mage::helper('buckaroo3extended')->__('Offline')
            ),
            array(
                'value' => Mage_Sales_Model_Order_Invoice::NOT_CAPTURE,
                'label' => Mage::helper('buckaroo3extended')->__('None')
            )
        );
    }
}
