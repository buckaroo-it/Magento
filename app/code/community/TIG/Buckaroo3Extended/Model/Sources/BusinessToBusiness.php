<?php

class TIG_Buckaroo3Extended_Model_Sources_BusinessToBusiness
{
    const AFTERPAY_BUSINESS_B2C  = '1';
    const AFTERPAY_BUSINESS_B2B  = '2';
    const AFTERPAY_BUSINESS_BOTH = '3';

    public function toOptionArray()
    {
        $array = array(
            array('value' => self::AFTERPAY_BUSINESS_B2C, 'label' => Mage::helper('buckaroo3extended')->__('B2C')),
            array('value' => self::AFTERPAY_BUSINESS_B2B, 'label' => Mage::helper('buckaroo3extended')->__('B2B')),
            array('value' => self::AFTERPAY_BUSINESS_BOTH, 'label' => Mage::helper('buckaroo3extended')->__('Both')),
        );
        return $array;
    }
}
