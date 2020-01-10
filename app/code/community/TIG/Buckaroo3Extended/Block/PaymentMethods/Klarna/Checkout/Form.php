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
class TIG_Buckaroo3Extended_Block_PaymentMethods_Klarna_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/klarna/checkout/form.phtml');
        parent::_construct();
    }

    /**
     * Get Calculated Payment Fee
     *
     * @return float|mixed
     */
    public function getPlainPaymentFee()
    {
        $paymentFeeWithMarkUp = $this->getMethodLabelAfterHtml(false);

        if (!$paymentFeeWithMarkUp) {
            return 0.00;
        }

        $plainPaymentFee = preg_replace('/[^0-9.,]/', '', $paymentFeeWithMarkUp);

        return $plainPaymentFee;
    }

    /**
     * Klarna demands that Firstname, LastName and Country are the same for Billing and Shipping Address
     *
     * @return bool
     */
    public function billingIsSameAsShipping()
    {
        $quote = $this->getQuote();

        $oBillingAddress = $quote->getBillingAddress()->getData();
        $oShippingAddress = $quote->getShippingAddress()->getData();

        // include only certain keys that are always different
        $includeKeys = array(
            'firstname',
            'lastname',
            'country_id',
        );

        $oBillingAddressFiltered = array_intersect_key($oBillingAddress, array_flip($includeKeys));
        $oShippingAddressFiltered = array_intersect_key($oShippingAddress, array_flip($includeKeys));

        //differentiate the addressess, when some data is different an array with changes will be returned
        $addressDiff = array_diff($oBillingAddressFiltered, $oShippingAddressFiltered);

        if (empty($addressDiff)) {
            return true;
        }

        return false;
    }
}
