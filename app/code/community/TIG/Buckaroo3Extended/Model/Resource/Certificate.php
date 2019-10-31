<?php 
class TIG_Buckaroo3Extended_Model_Resource_Certificate extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('buckaroo3extended/certificate', 'certificate_id');
    }
}
