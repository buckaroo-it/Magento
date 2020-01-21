<?php
class Buckaroo_Buckaroo3Extended_Model_Refund_Sources_StatusesCompleteFailed extends Varien_Object
{
    static public function toOptionArray()
    {
        $state = 'complete';
        
        $statuses = Mage::getSingleton('sales/order_config')->getStateStatuses($state);
         
        $options = array();
        $options[] = array('value' => '', 'label' => Mage::helper('buckaroo3extended')->__('-- Please Select --'));
        foreach($statuses as $value => $label)
        {
            $options[] = array('value' => $value, 'label' => $label);
        }
        
        return $options;
    }
}
