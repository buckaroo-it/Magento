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
class Buckaroo_Buckaroo3Extended_Model_Sources_PaymentFlow
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('buckaroo3extended');

        $array = array(
            array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_ORDER,
                'label' => $helper->__('Combined')
            ),
            array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'label' => $helper->__('Separate authorize and capture')
            ),
        );

        return $array;
    }
}

