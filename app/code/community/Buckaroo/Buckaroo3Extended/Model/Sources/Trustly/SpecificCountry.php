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
class Buckaroo_Buckaroo3Extended_Model_Sources_Trustly_SpecificCountry
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('buckaroo3extended');

        $array = array(
            array(
                'value' => 'DE',
                'label' => $helper->__('Germany')
            ),
            array(
                'value' => 'DK',
                'label' => $helper->__('Denmark')
            ),
            array(
                'value' => 'EE',
                'label' => $helper->__('Estonia')
            ),
            array(
                'value' => 'ES',
                'label' => $helper->__('Spain')
            ),
            array(
                'value' => 'FI',
                'label' => $helper->__('Finland')
            ),
            array(
                'value' => 'NL',
                'label' => $helper->__('Netherlands')
            ),
            array(
                'value' => 'NO',
                'label' => $helper->__('Norway')
            ),
            array(
                'value' => 'PL',
                'label' => $helper->__('Poland')
            ),
            array(
                'value' => 'SE',
                'label' => $helper->__('Sweden')
            ),
            array(
                'value' => 'GB',
                'label' => $helper->__('United Kingdom')
            ),
        );

        return $array;
    }
}
