<?php
class TIG_Buckaroo3Extended_Block_PaymentMethods_Afterpay_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/afterpay/checkout/form.phtml');
        parent::_construct();
    }

    public function getPaymethod()
    {
        return Mage::getStoreConfig(
            'buckaroo/' . $this->getMethodCode() . '/paymethod', Mage::app()->getStore()->getStoreId()
        );
    }

    public function getBusiness()
    {
        return Mage::getStoreConfig(
            'buckaroo/' . $this->getMethodCode() . '/business', Mage::app()->getStore()->getStoreId()
        );
    }

    public function getCompanyCOCRegistration()
    {
        return $this->getSession()->getData($this->getMethodCode() . '_BPE_CompanyCOCRegistration');
    }

    public function getCompanyName()
    {
        return $this->getSession()->getData($this->getMethodCode() . '_BPE_CompanyName');
    }

    public function getBusinessSelect()
    {
        return $this->getSession()->getData($this->getMethodCode() . '_BPE_BusinessSelect');
    }

    /**
     * @return string
     */
    public function getTosUrl()
    {
        $paymentMethod = $this->getPaymethod();

        switch ($paymentMethod) {
            case TIG_Buckaroo3Extended_Model_Sources_AcceptgiroDirectdebit::AFTERPAY_PAYMENT_METHOD_DIGIACCEPT:
                $url = $this->getDigiacceptUrl();
                break;
            case TIG_Buckaroo3Extended_Model_Sources_AcceptgiroDirectdebit::AFTERPAY_PAYMENT_METHOD_ACCEPTGIRO:
            default:
                $url = $this->getAcceptgiroUrl();
                break;
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getB2CUrl()
    {
        $billingCountry = $this->getBillingCountry();

        switch ($billingCountry) {
            case 'BE':
                $url = 'https://www.afterpay.be/be/footer/betalen-met-afterpay/betalingsvoorwaarden';
                break;
            case 'NL':
            default:
                $url = 'https://www.afterpay.nl/nl/algemeen/betalen-met-afterpay/betalingsvoorwaarden';
                break;
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getB2BUrl()
    {
        $billingCountry = $this->getBillingCountry();
        $url = 'https://www.afterpay.nl/nl/algemeen/betalen-met-afterpay/betalingsvoorwaarden';

        if ($billingCountry == 'NL') {
            $url = 'https://www.afterpay.nl/nl/algemeen/zakelijke-partners/betalingsvoorwaarden-zakelijk';
        }

        return $url;
    }

    /**
     * @return string
     */
    protected function getAcceptgiroUrl()
    {
        $url = $this->getDigiacceptUrl();

        return $url;
    }

    /**
     * @return string
     */
    protected function getDigiacceptUrl()
    {
        $businessIsBtoC = $this->businessIsB2C();

        switch ($businessIsBtoC) {
            case false:
                $url = $this->getB2BUrl();
                break;
            case true:
            default:
                $url = $this->getB2CUrl();
                break;
        }

        return $url;
    }

    /**
     * @return bool
     */
    protected function businessIsB2C()
    {
        $result = true;
        $business = $this->getBusiness();

        if ($business == TIG_Buckaroo3Extended_Model_Sources_BusinessToBusiness::AFTERPAY_BUSINESS_B2B) {
            $result = false;
        }

        return $result;
    }
}
