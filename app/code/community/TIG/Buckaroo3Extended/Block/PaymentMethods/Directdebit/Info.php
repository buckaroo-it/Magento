<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Directdebit_Info extends Mage_Payment_Block_Info
{
    protected $_mandateReference;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('buckaroo3extended/directdebit/info.phtml');
    }

    public function getMandateReference()
    {
        if ($this->_mandateReference === null) {
            $this->_convertAdditionalData();
        }

        return $this->_mandateReference;
    }

    protected function _convertAdditionalData()
    {
        $details = unserialize($this->getInfo()->getAdditionalData());
        if (is_array($details)) {
            $this->_mandateReference = isset($details['mandate_reference']) ? (string)$details['mandate_reference']
                : '';
        } else {
            $this->_mandateReference = '';
        }

        return $this;
    }
}
