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
