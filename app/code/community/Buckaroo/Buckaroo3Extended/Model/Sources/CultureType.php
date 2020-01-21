<?php
class Buckaroo_Buckaroo3Extended_Model_Sources_CultureType
{
    public function toOptionArray()
    {
        $array = array(
             array('value' => 'billing', 'label' => Mage::helper('buckaroo3extended')->__('Billing Address')),
             array('value' => 'store', 'label' => Mage::helper('buckaroo3extended')->__('Store Settings')),
        );
        return $array;
    }
}
