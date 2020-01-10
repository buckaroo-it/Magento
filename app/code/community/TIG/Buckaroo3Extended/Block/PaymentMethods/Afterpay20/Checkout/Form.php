<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_Block_PaymentMethods_Afterpay20_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
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
