<?php
class Buckaroo_Buckaroo3Extended_Model_Sources_PaymentMethodArea
{
    public function toOptionArray()
    {
        $array = array(
             array('value' => 'frontend', 'label' => Mage::helper('buckaroo3extended')->__('Frontend')),
             array('value' => 'backend', 'label' => Mage::helper('buckaroo3extended')->__('Backend')),
             array('value' => 'both', 'label' => Mage::helper('buckaroo3extended')->__('Frontend and Backend')),
        );
        return $array;
    }
}
