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
class TIG_Buckaroo3Extended_Model_Response_MasterPass extends TIG_Buckaroo3Extended_Model_Response_Return
{
    /**
     * @return bool
     */
    public function processReturn()
    {
        //check if the push is valid
        $canProcess = $this->_canProcessPush();

        $this->_debugEmail .= "can the order be processed? " . $canProcess . "\n";

        if (!$canProcess) {
            $this->_verifyError();
        }

        $masterPassData = $this->_processMasterPass($this->_postArray);

        /** return object to get data from observer */
        $return = new Varien_Object();
        Mage::dispatchEvent('masterpass_generate_summary_url', array('return' => $return, 'masterpass_data' => $masterPassData));

        if (!$return->getPath()) {
            Mage::throwException(
                Mage::helper('buckaroo3extended')->__('No MasterPass summary url available')
            );
        }

        $masterPassUrlData['path'] = $return->getPath();

        return $masterPassUrlData;
    }

    /**
     * @param $postData
     *
     * @return array
     */
    protected function _processMasterPass($postData)
    {
        $masterPassData = array(
            'order' => array(
                'quote_id'  => str_replace('quote_', '', $postData['brq_invoicenumber']),
                'addresses' => array(
                    'billing' => array(
                        'firstname'     => urldecode($postData['brq_SERVICE_masterpass_CustomerFirstName']),
                        'lastname'      => urldecode($postData['brq_SERVICE_masterpass_CustomerLastName']),
                        'city'          => urldecode($postData['brq_SERVICE_masterpass_BillingCity']),
                        'country_id'    => urldecode($postData['brq_SERVICE_masterpass_BillingCountry']),
                        'street'        => urldecode($postData['brq_SERVICE_masterpass_BillingLine1']),
                        'postcode'      => urldecode($postData['brq_SERVICE_masterpass_BillingPostalCode']),
                        'region'        => urldecode($postData['brq_SERVICE_masterpass_BillingCountrySubdivision']),
                        'telephone'     => urldecode($postData['brq_SERVICE_masterpass_BillingRecipientPhoneNumber']),
                    ),
                    'shipping' => array(
                        'firstname'     => urldecode($postData['brq_SERVICE_masterpass_CustomerFirstName']),
                        'lastname'      => urldecode($postData['brq_SERVICE_masterpass_CustomerLastName']),
                        'city'          => urldecode($postData['brq_SERVICE_masterpass_ShippingCity']),
                        'country_id'    => urldecode($postData['brq_SERVICE_masterpass_ShippingCountry']),
                        'street'        => urldecode($postData['brq_SERVICE_masterpass_ShippingLine1']),
                        'postcode'      => urldecode($postData['brq_SERVICE_masterpass_ShippingPostalCode']),
                        'region'        => urldecode($postData['brq_SERVICE_masterpass_ShippingCountrySubdivision']),
                        'telephone'     => urldecode($postData['brq_SERVICE_masterpass_ShippingRecipientPhoneNumber']),
                    ),
                ),
            ),
            'transaction' => array(
                'merchant_checkout_id'  => urldecode($postData['brq_SERVICE_masterpass_MerchantCheckoutId']),
                'request_token'         => urldecode($postData['brq_SERVICE_masterpass_RequestToken']),
            ),
            'customer' => array(
                'firstname' => urldecode($postData['brq_SERVICE_masterpass_CustomerFirstName']),
                'fullname' => urldecode($postData['brq_SERVICE_masterpass_CustomerFullName']),
                'lastname' => urldecode($postData['brq_SERVICE_masterpass_CustomerLastName']),
                'email' => urldecode($postData['brq_SERVICE_masterpass_customeremail']),
            ),
            'creditcard' => array(
                'card_holder_name'      => urldecode($postData['brq_SERVICE_masterpass_CardHolderName']),
                'card_number_ending'    => urldecode($postData['brq_SERVICE_masterpass_CardNumberEnding']),
            ),
        );

        // Check phone numbers for at least a value
        if (empty($masterPassData['order']['addresses']['billing']['telephone'])) {
            $masterPassData['order']['addresses']['billing']['telephone'] = '-';
        }

        if (empty($masterPassData['order']['addresses']['shipping']['telephone'])) {
            $masterPassData['order']['addresses']['shipping']['telephone'] = '-';
        }

        return $masterPassData;
    }

    /**
     * Determines the signature using array sorting and the SHA1 hash algorithm
     *
     * @return string $signature
     */
    protected function _calculateSignature()
    {
        if (isset($this->_postArray['isOldPost']) && $this->_postArray['isOldPost'])
        {
            return $this->_calculateOldSignature();
        }

        $origArray = $this->_postArray;
        unset($origArray['brq_signature']);

        //sort the array
        $sortableArray = $this->buckarooSort($origArray);

        //turn into string and add the secret key to the end
        $signatureString = '';
        foreach($sortableArray as $key => $value) {
            if ('brq_SERVICE_masterpass_CustomerPhoneNumber' !== $key
                && 'brq_SERVICE_masterpass_ShippingRecipientPhoneNumber' !== $key
            ) {
                $value = urldecode($value);
            }

            $signatureString .= $key . '=' . $value;
        }

        $signatureString .= Mage::getStoreConfig('buckaroo/buckaroo3extended/digital_signature', Mage::app()->getStore()->getId());

        $this->_debugEmail .= "\nSignaturestring: {$signatureString}\n";

        //return the SHA1 encoded string for comparison
        $signature = SHA1($signatureString);

        $this->_debugEmail .= "\nSignature: {$signature}\n";

        return $signature;
    }


    /**
     * Checks if the post received is valid by checking its signature field.
     * This field is unique for every payment and every store.
     *
     * @param array $response
     * @return array
     */
    protected function _canProcessPush($isReturn = false, $response = array())
    {
        $correctSignature = false;
        $signature        = $this->_calculateSignature();
        if ($signature === $this->_postArray['brq_signature']) {
            $correctSignature = true;
        }

        return $correctSignature;
    }

    protected function _verifyError()
    {
        $this->_debugEmail .= "The transaction's authenticity was not verified. \n";
        Mage::getSingleton('core/session')->addNotice(
            Mage::helper('buckaroo3extended')->__('We are currently unable to retrieve the status of your transaction. If you do not receive an e-mail regarding your order within 30 minutes, please contact the shop owner.')
        );

        $returnLocation = Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/failure_redirect', Mage::app()->getStore()->getId());
        $returnUrl = Mage::getUrl($returnLocation, array('_secure' => true));

        $this->_debugEmail .= 'Redirecting user to...' . $returnUrl . "\n";

        $this->sendDebugEmail();

        Mage::app()->getResponse()->clearHeaders();
        Mage::app()->getResponse()->setRedirect($returnUrl)->sendResponse();

        return;
    }
}
