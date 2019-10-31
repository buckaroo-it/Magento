<?php
class TIG_Buckaroo3Extended_Model_Sources_TestLive
{
    public function toOptionArray()
    {
        $array = array(
             array('value' => '1', 'label' => Mage::helper('buckaroo3extended')->__('Test')),
             array('value' => '0', 'label' => Mage::helper('buckaroo3extended')->__('Live')),
        );
        return $array;
    }
}
