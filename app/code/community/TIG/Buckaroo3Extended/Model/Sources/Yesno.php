<?php
class TIG_Buckaroo3Extended_Model_Sources_Yesno
{
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>Mage::helper('buckaroo3extended')->__('Yes')),
            array('value' => 0, 'label'=>Mage::helper('buckaroo3extended')->__('No')),
        );
    }
}
