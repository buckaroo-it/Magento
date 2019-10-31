<?php

class TIG_Buckaroo3Extended_Model_Adminhtml_System_Config_Backend_Buckaroo_Certificate
    extends Mage_Core_Model_Config_Data
{
    public function _afterSave()
    {
        Mage::getModel('buckaroo3extended/certificate_certificate')->uploadAndImport($this);
    }
}
