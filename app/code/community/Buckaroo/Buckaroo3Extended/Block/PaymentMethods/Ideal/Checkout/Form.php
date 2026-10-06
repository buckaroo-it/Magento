<?php
class Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Ideal_Checkout_Form
    extends Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/ideal/checkout/form.phtml');
        $this->setIssuers();
        parent::_construct();
    }

    public function setIssuers()
    {
        $issuersArray = array(
            'version1' => array(
                '0031' => array(
                    'name' => 'ABN AMRO',
                    'logo' => 'issuers/abnamro.svg',
                ),
                '0761' => array(
                    'name' => 'ASN Bank',
                    'logo' => 'issuers/asnbank.svg',
                ),
                '0721' => array(
                    'name' => 'ING',
                    'logo' => 'issuers/ing.svg',
                ),
                '0021' => array(
                    'name' => 'Rabobank',
                    'logo' => 'issuers/rabobank.svg',
                ),
                '0751' => array(
                    'name' => 'SNS Bank',
                    'logo' => 'issuers/sns.svg',
                ),
                '0771' => array(
                    'name' => 'RegioBank',
                    'logo' => 'issuers/regiobank.svg',
                ),
                '0511' => array(
                    'name' => 'Triodos Bank',
                    'logo' => 'issuers/triodos.svg',
                ),
                '0161' => array(
                    'name' => 'Van Lanschot Kempen',
                    'logo' => 'issuers/vanlanschot.svg',
                ),
                '0801' => array(
                    'name' => 'Knab',
                    'logo' => 'issuers/knab.svg',
                ),
            ),
            'version2' => array(
                'ABNANL2A' => array(
                    'name' => 'ABN AMRO',
                    'logo' => 'issuers/abnamro.svg',
                ),
                'ASNBNL21' => array(
                    'name' => 'ASN Bank',
                    'logo' => 'issuers/asnbank.svg',
                ),
                'BUNQNL2A' => array(
                    'name' => 'bunq',
                    'logo' => 'issuers/bunq.svg',
                ),
                'INGBNL2A' => array(
                    'name' => 'ING',
                    'logo' => 'issuers/ing.svg',
                ),
                'KNABNL2H' => array(
                    'name' => 'Knab bank',
                    'logo' => 'issuers/knab.svg',
                ),
                'RABONL2U' => array(
                    'name' => 'Rabobank',
                    'logo' => 'issuers/rabobank.svg',
                ),
                'RBRBNL21' => array(
                    'name' => 'RegioBank',
                    'logo' => 'issuers/regiobank.svg',
                ),
                'SNSBNL2A' => array(
                    'name' => 'SNS Bank',
                    'logo' => 'issuers/sns.svg',
                ),
                'TRIONL2U' => array(
                    'name' => 'Triodos Bank',
                    'logo' => 'issuers/triodos.svg',
                ),
                'FVLBNL22' => array(
                    'name' => 'Van Lanschot Kempen',
                    'logo' => 'issuers/vanlanschot.svg',
                ),
                'REVOLT21' => array(
                    'name' => 'Revolut',
                    'logo' => 'issuers/revolut.svg',
                ),
                'BITSNL2A' => array(
                    'name' => 'Yoursafe',
                    'logo' => 'issuers/yoursafe.svg',
                ),
            ),
        );

        $issuers = new Varien_Object($issuersArray);
        $this->issuers = $issuers;

        return $this;
    }

    public function canShowIssuers() {
        return Mage::getStoreConfig(
            'buckaroo/'.$this->getMethodCode().'/show_issuers', Mage::app()->getStore()->getId()
        ) != 0;
    }

    public function getIssuerList()
    {
        $version = (int)Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_ideal/service_version', Mage::app()->getStore()->getId()
        );
        $issuers = $this->getIssuers();

        if ($version === 2) {
            return $issuers->getVersion2();
        }

        return $issuers->getVersion1();
    }
}
