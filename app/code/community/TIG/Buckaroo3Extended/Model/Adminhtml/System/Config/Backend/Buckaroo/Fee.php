<?php

class TIG_Buckaroo3Extended_Model_Adminhtml_System_Config_Backend_Buckaroo_Fee extends Mage_Core_Model_Config_Data
{
    public function _beforeSave()
    {
        $groups = $this->getGroups();
        foreach ($groups as &$group) {
            $fields = $group['fields'];
            if (array_key_exists('payment_fee', $fields)) {
                $fee = $group['fields']['payment_fee']['value'];
                if ($fee) {
                    $group['fields']['payment_fee']['value'] = $this->_validateFee($fee);
                }
            }
        }

        $this->setGroups($groups);

        return parent::_beforeSave();
    }

    protected function _validateFee($fee)
    {
        $fee = str_replace(',', '.', $fee);
        preg_match("#^0*(100(\.00?)?|[0-9]?[0-9](\.[0-9][0-9]?)?)%?$#", $fee, $matches);

        if (empty($matches)) {
            Mage::throwException(
                Mage::helper('buckaroo3extended')->__(
                    'Fee value is improperly formatted. Fee must be a positive number or a percentage with a ' .
                    'single decimal seperator.'
                )
            );
        }

        return $matches[0];
    }
}
