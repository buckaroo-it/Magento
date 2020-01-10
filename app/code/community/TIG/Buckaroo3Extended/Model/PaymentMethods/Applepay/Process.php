<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
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

class TIG_Buckaroo3Extended_Model_PaymentMethods_Applepay_Process extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    /**
     * Calculate Buckaroo payment fee (incl. or excl. tax)
     *
     * @param $address
     *
     * @return mixed
     */
    public function calculateBuckarooFee($address)
    {
        $buckarooFee    = $address->getData('buckaroo_fee') ?: 0;
        $buckarooFeeTax = $address->getData('buckaroo_fee_tax') ?: 0;
        
        if (count($address->getAppliedTaxes()) == 0) {
            return $buckarooFee;
        }
        
        return $buckarooFee + $buckarooFeeTax;
    }
    
    /**
     * @param array $data
     *
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function sanitizeParams(array $data)
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return $data;
        }
        
        array_walk_recursive($data, function (&$value) {
            $value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        });
        
        return $data;
    }
    
    /**
     * @param $address
     * @param $quoteTotals
     *
     * @return array
     */
    public function gatherTotals($address, $quoteTotals)
    {
        $totals = array(
            'subTotal'   => $quoteTotals['subtotal']->getValue(),
            'discount'   => isset($quoteTotals['discount']) ? $quoteTotals['discount']->getValue() : null,
            'shipping'   => $address->getData('shipping_incl_tax'),
            'grandTotal' => $quoteTotals['grand_total']->getValue()
        );
        
        return $totals;
    }
    
    /**
     * @param        $wallet
     * @param string $type
     *
     * @return array
     */
    public function processAddressFromWallet($wallet, $type = 'shipping')
    {
        $address = array(
            'prefix'     => '',
            'firstname'  => isset($wallet['givenName']) ? $wallet['givenName'] : '',
            'middlename' => '',
            'lastname'   => isset($wallet['familyName']) ? $wallet['familyName'] : '',
            'street'     => array(
                '0' => isset($wallet['addressLines'][0]) ? $wallet['addressLines'][0] : '',
                '1' => isset($wallet['addressLines'][1]) ? $wallet['addressLines'][1] : null
            ),
            'city'       => isset($wallet['locality']) ? $wallet['locality'] : '',
            'country_id' => isset($wallet['countryCode']) ? $wallet['countryCode'] : '',
            'region'     => isset($wallet['administrativeArea']) ? $wallet['administrativeArea'] : '',
            'region_id'  => '',
            'postcode'   => isset($wallet['postalCode']) ? $wallet['postalCode'] : '',
            'telephone'  => isset($wallet['phoneNumber']) ? $wallet['phoneNumber'] : 'N/A',
            'fax'        => '',
            'vat_id'     => ''
        );
        
        if ($type == 'shipping') {
            $address['email'] = isset($wallet['emailAddress']) ? $wallet['emailAddress'] : '';
        }
        
        return $address;
    }
}
