<?php
class Buckaroo_Buckaroo3Extended_Model_Observer_ApplepayAfterSuccess extends Mage_Core_Model_Abstract
{
    public function afterSuccessAction(Varien_Event_Observer $observer)
    {
        $orderId = $observer->getOrderIds()[0];
        if($paymentMethod = Mage::getModel('sales/order')->load($orderId)->getPayment()){
            $paymentMethod = Mage::getModel('sales/order')->load($orderId)->getPayment()->getMethod();
            
            if ($paymentMethod === 'buckaroo3extended_applepay') {
                /** @var Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Applepay_Process $process */
                $process = Mage::getModel(' buckaroo3extended/paymentMethods_applepay_process');
                
                $process->restoreCart();
            }
        }
    }
}
