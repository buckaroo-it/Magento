<?php
class Buckaroo_Buckaroo3Extended_Model_Sources_AcceptgiroDirectdebit
{
    const AFTERPAY_PAYMENT_METHOD_ACCEPTGIRO = 'afterpayacceptgiro';
    const AFTERPAY_PAYMENT_METHOD_DIGIACCEPT = 'afterpaydigiaccept';

    public function toOptionArray()
    {
        $array = array(
             array(
                 'value' => self::AFTERPAY_PAYMENT_METHOD_ACCEPTGIRO,
                 'label' => Mage::helper('buckaroo3extended')->__('Acceptgiro')
             ),
             array(
                 'value' => self::AFTERPAY_PAYMENT_METHOD_DIGIACCEPT,
                 'label' => Mage::helper('buckaroo3extended')->__('Digiaccept')
             ),
        );
        return $array;
    }
}
