<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Idealprocessing_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Ideal_Checkout_Form
{
    public function getIssuerList()
    {
        $version = (int)Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_idealprocessing/service_version', Mage::app()->getStore()->getId()
        );
        $issuers = $this->getIssuers();

        if ($version === 2) {
            return $issuers->getVersion2();
        }

        return $issuers->getVersion1();
    }
}
