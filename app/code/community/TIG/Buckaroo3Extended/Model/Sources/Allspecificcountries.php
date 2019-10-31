<?php
class TIG_Buckaroo3Extended_Model_Sources_Allspecificcountries
{
    public function toOptionArray()
    {
        return array(
            array('value'=>0, 'label'=>Mage::helper('buckaroo3extended')->__('All Allowed Countries')),
            array('value'=>1, 'label'=>Mage::helper('buckaroo3extended')->__('Specific Countries')),
        );
    }
}
