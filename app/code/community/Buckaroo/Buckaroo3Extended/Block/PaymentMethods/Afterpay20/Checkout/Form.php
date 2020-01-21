<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */
class Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Afterpay20_Checkout_Form
    extends Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/afterpay20/checkout/form.phtml');
        parent::_construct();
    }

    /**
     * @param string $countryFormatAfterpay
     * @return string
     */
    public function getAcceptanceUrl($countryFormatAfterpay = '')
    {
        if (!$countryFormatAfterpay) {
            $billingCountry = $this->getBillingCountry();

            switch ($billingCountry) {
                case 'DE' :
                    $countryFormatAfterpay = 'de_de';
                    break;
                case 'AT' :
                    $countryFormatAfterpay = 'de_at';
                    break;
                case 'NL' :
                    $countryFormatAfterpay = 'nl_nl';
                    break;
                case 'BE' :
                    $countryFormatAfterpay = 'nl_be';
                    break;
                case 'FI' :
                    $countryFormatAfterpay = 'fi_fi';
                    break;
                default:
                    $countryFormatAfterpay = 'en_nl';
                    break;
            }
        }

        $url = "https://documents.myafterpay.com/consumer-terms-conditions/" . $countryFormatAfterpay . "/";

        return $url;
    }
}
