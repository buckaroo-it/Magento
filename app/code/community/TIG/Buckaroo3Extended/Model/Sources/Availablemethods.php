<?php 
class TIG_Buckaroo3Extended_Model_Sources_Availablemethods
{
    public function toOptionArray()
    {
        $helper = Mage::helper('buckaroo3extended');

        $array = array(
             array('value' => 'all', 'label' => $helper->__('all')),
             array('value' => 'amex', 'label' => $helper->__('American Express')),
             array('value' => 'directdebit', 'label' => $helper->__('Eenmalige Machtiging')),
             array('value' => 'giropay', 'label' => $helper->__('Giropay')),
             array('value' => 'ideal', 'label' => $helper->__('iDEAL')),
             array('value' => 'idealprocessing', 'label' => $helper->__('iDEAL Processing')),
             array('value' => 'mastercard', 'label' => $helper->__('Mastercard')),
             array('value' => 'onlinegiro', 'label' => $helper->__('Online Giro')),
             array('value' => 'paypal', 'label' => $helper->__('PayPal')),
             array('value' => 'paysafecard', 'label' => $helper->__('Paysafecard')),
             array('value' => 'sofortueberweisung', 'label' => $helper->__('Sofort Banking')),
             array('value' => 'transfer', 'label' => $helper->__('Overboeking')),
             array('value' => 'visa', 'label' => $helper->__('Visa')),
             array('value' => 'maestro', 'label' => $helper->__('eMaestro')),
             array('value' => 'visaelectron', 'label' => $helper->__('Visa Electron')),
             array('value' => 'vpay', 'label' => $helper->__('V PAY')),
             array('value' => 'bancontactmrcash', 'label' => $helper->__('Bancontact / Mr. Cash')),
        );
        return $array;
    }
}
