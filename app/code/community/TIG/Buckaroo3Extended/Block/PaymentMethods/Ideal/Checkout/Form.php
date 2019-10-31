<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Ideal_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
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
                    'logo' => 'logo_abn_s.gif',
                ),
                '0761' => array(
                    'name' => 'ASN Bank',
                    'logo' => 'icon_asn.gif',
                ),
                '0721' => array(
                    'name' => 'ING',
                    'logo' => 'logo_ing_s.gif',
                ),
                '0021' => array(
                    'name' => 'Rabobank',
                    'logo' => 'logo_rabo_s.gif',
                ),
                '0751' => array(
                    'name' => 'SNS Bank',
                    'logo' => 'logo_sns_s.gif',
                ),
                '0771' => array(
                    'name' => 'RegioBank',
                    'logo' => 'logo_sns_s.gif',
                ),
                '0511' => array(
                    'name' => 'Triodos Bank',
                    'logo' => 'logo_triodos.gif',
                ),
                '0161' => array(
                    'name' => 'Van Lanschot',
                    'logo' => 'logo_lanschot_s.gif',
                ),
                '0801' => array(
                    'name' => 'Knab',
                    'logo' => 'logo_knab_s.gif',
                ),
            ),
            'version2' => array(
                'ABNANL2A' => array(
                    'name' => 'ABN AMRO',
                    'logo' => 'logo_abn_s.gif',
                ),
                'ASNBNL21' => array(
                    'name' => 'ASN Bank',
                    'logo' => 'icon_asn.gif',
                ),
                'BUNQNL2A' => array(
                    'name' => 'bunq',
                    'logo' => 'logo_bunq_s.gif',
                ),
                'INGBNL2A' => array(
                    'name' => 'ING',
                    'logo' => 'logo_ing_s.gif',
                ),
                'KNABNL2H' => array(
                    'name' => 'Knab bank',
                    'logo' => 'logo_knab_s.gif',
                ),
                'MOYONL21' => array(
                    'name' => 'Moneyou',
                    'logo' => 'logo_moneyou_s.gif',
                ),
                'RABONL2U' => array(
                    'name' => 'Rabobank',
                    'logo' => 'logo_rabo_s.gif',
                ),
                'RBRBNL21' => array(
                    'name' => 'RegioBank',
                    'logo' => 'logo_sns_s.gif',
                ),
                'SNSBNL2A' => array(
                    'name' => 'SNS Bank',
                    'logo' => 'logo_sns_s.gif',
                ),
                'TRIONL2U' => array(
                    'name' => 'Triodos Bank',
                    'logo' => 'logo_triodos.gif',
                ),
                'FVLBNL22' => array(
                    'name' => 'Van Lanschot',
                    'logo' => 'logo_lanschot_s.gif',
                ),
                'HANDNL2A' => array(
                    'name' => 'Handelsbanken',
                    'logo' => 'logo_handelsbanken_s.gif',
                ),
            ),
        );

        $issuers = new Varien_Object($issuersArray);
        $this->issuers = $issuers;

        return $this;
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
