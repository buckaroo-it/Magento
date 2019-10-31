<?php
class TIG_Buckaroo3Extended_Model_Sources_StatusesPending extends Varien_Object
{
    static public function toOptionArray()
    {
        $state='new';
        $statuses=Mage::getSingleton('sales/order_config')->getStateStatuses($state);
         
        $options=array();
        $options[]=array('value'=>'', 'label'=>Mage::helper('buckaroo3extended')->__('-- Please Select --'));
        foreach ($statuses as $value=>$label) {
            $options[]=array('value'=>$value, 'label'=>$label);
        }
        
        return $options;
    }
}
