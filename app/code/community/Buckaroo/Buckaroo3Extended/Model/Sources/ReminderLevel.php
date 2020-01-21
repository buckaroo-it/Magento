<?php
class Buckaroo_Buckaroo3Extended_Model_Sources_ReminderLevel
{
    public function toOptionArray()
    {
        $array = array(
             array('value' => '4', 'label' => Mage::helper('buckaroo3extended')->__('4')),
             array('value' => '3', 'label' => Mage::helper('buckaroo3extended')->__('3')),
             array('value' => '2', 'label' => Mage::helper('buckaroo3extended')->__('2')),
             array('value' => '1', 'label' => Mage::helper('buckaroo3extended')->__('1')),
        );
        return $array;
    }
}
