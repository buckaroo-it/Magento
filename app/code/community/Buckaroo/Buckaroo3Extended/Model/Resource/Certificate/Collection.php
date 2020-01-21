<?php 
class Buckaroo_Buckaroo3Extended_Model_Resource_Certificate_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {    
        parent::_construct();
        $this->_init('buckaroo3extended/certificate');
    }
}
