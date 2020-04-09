<?php
class Buckaroo_Buckaroo3Extended_Model_Sources_RefundTypes
{
    const WITH_ORDER_LINES = '';
    const WITHOUT_ORDER_LINES = 'without';

    public function toOptionArray()
    {
        $array = array(
             array(
                 'value' => self::WITH_ORDER_LINES,
                 'label' => Mage::helper('buckaroo3extended')->__('With order lines')
             ),
             array(
                 'value' => self::WITHOUT_ORDER_LINES,
                 'label' => Mage::helper('buckaroo3extended')->__('Without order lines')
             ),
        );
        return $array;
    }
}
