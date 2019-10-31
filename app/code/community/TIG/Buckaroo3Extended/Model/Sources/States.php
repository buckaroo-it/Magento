<?php
class TIG_Buckaroo3Extended_Model_Sources_States extends Varien_Object
{
    static public function toOptionArray()
    {
        $states=Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates();
        
        $options=array();
        $options['']=Mage::helper('buckaroo3extended')->__('-- Please Select --');
        
        foreach ($states as $value=>$label) {
            $options[]=array('value'=>$label, 'label'=>$label);
        }
        
        return $options;
    }
}
