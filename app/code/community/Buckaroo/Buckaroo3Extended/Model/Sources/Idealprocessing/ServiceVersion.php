<?php
class Buckaroo_Buckaroo3Extended_Model_Sources_Idealprocessing_ServiceVersion
{
    public function toOptionArray()
    {
        $helper = Mage::helper('buckaroo3extended');

        $array = array(
            array(
                'label' => $helper->__('1'),
                'value' => 1,
            ),
            array(
                'label' => $helper->__('2 (SEPA)'),
                'value' => 2,
            ),
        );

        return $array;
    }
}
