<?php
class TIG_Buckaroo3Extended_Block_Adminhtml_System_Config_KlarnaCheck
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'buckaroo3extended/system/config/klarnaCheck.phtml';

    public function getIsKlarnaEnabled()
    {
        return Mage::helper('buckaroo3extended')->getIsKlarnaEnabled();
    }

    // @codingStandardsIgnoreLine
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
