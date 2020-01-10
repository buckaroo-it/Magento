<?php
/**  ____________  _     _ _ ________  ___  _ _  _______   ___  ___  _  _ _ ___
 *   \_ _/ \_ _/ \| |   |_| \ \_ _/  \| _ || \ |/  \_ _/  / __\| _ |/ \| | | _ \
 *    | | | | | ' | |_  | |   || | '_/|   /|   | '_/| |  | |_ \|   / | | | | __/
 *    |_|\_/|_|_|_|___| |_|_\_||_|\__/|_\_\|_\_|\__/|_|   \___/|_\_\\_/|___|_|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

class TIG_Buckaroo3Extended_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    const BUCKAROO_SUCCESS           = 'BUCKAROO_SUCCESS';
    const BUCKAROO_FAILED            = 'BUCKAROO_FAILED';
    const BUCKAROO_ERROR             = 'BUCKAROO_ERROR';
    const BUCKAROO_NEUTRAL           = 'BUCKAROO_NEUTRAL';
    const BUCKAROO_PENDING_PAYMENT   = 'BUCKAROO_PENDING_PAYMENT';
    const BUCKAROO_INCORRECT_PAYMENT = 'BUCKAROO_INCORRECT_PAYMENT';
    const BUCKAROO_REJECTED          = 'BUCKAROO_REJECTED';

    /**
     *  @var Mage_Sales_Model_Order|Mage_Sales_Model_Quote $_order
     */
    protected $_order = '';
    protected $_debugEmail;
    protected $_billingInfo = '';
    protected $_session = '';
    protected $_storeId = '';

    /**
     *  List of possible response codes sent by buckaroo.
     *  This is the list for the BPE 3.0 gateway.
     */
    public    $responseCodes = array(
            190 => array(
                       'message' => 'Success',
                       'status'  => self::BUCKAROO_SUCCESS,
                   ),
            490 => array(
                       'message' => 'Payment failure',
                       'status'  => self::BUCKAROO_FAILED,
                   ),
            491 => array(
                       'message' => 'Validation error',
                       'status'  => self::BUCKAROO_FAILED,
                   ),
            492 => array(
                       'message' => 'Technical error',
                       'status'  => self::BUCKAROO_ERROR,
                   ),
            690 => array(
                       'message' => 'Payment rejected',
                       'status'  => self::BUCKAROO_REJECTED,
                   ),
            790 => array(
                       'message' => 'Waiting for user input',
                       'status'  => self::BUCKAROO_PENDING_PAYMENT,
                   ),
            791 => array(
                       'message' => 'Waiting for processor',
                       'status'  => self::BUCKAROO_PENDING_PAYMENT,
                   ),
            792 => array(
                       'message' => 'Waiting on consumer action',
                       'status'  => self::BUCKAROO_PENDING_PAYMENT,
                   ),
            793 => array(
                       'message' => 'Payment on hold',
                       'status'  => self::BUCKAROO_PENDING_PAYMENT,
                   ),
            890 => array(
                       'message' => 'Cancelled by consumer',
                       'status'  => self::BUCKAROO_FAILED,
                   ),
            891 => array(
                       'message' => 'Cancelled by merchant',
                       'status'  => self::BUCKAROO_FAILED,
                   ),
        );

    /**
     * Retrieves instance of the last used order
     */
    protected function _loadLastOrder()
    {
        if (!empty($this->_order)) {
            return;
        }

        $session = Mage::getSingleton('checkout/session');
        $orderId = $session->getLastRealOrderId();
        if (!empty($orderId)) {
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        }

        return $this;
    }

    public function setOrder($order)
    {
        $this->_order = $order;

        return $this;
    }

    public function getOrder()
    {
        return $this->_order;
    }

    public function setLastOrder($order)
    {
        $this->_order = $order;

        return $this;
    }

    public function getLastOrder()
    {
        return $this->_order;
    }

    public function setDebugEmail($debugEmail)
    {
        $this->_debugEmail = $debugEmail;

        return $this;
    }

    public function getDebugEmail()
    {
        return $this->_debugEmail;
    }

    public function setBillingInfo($billingInfo)
    {
        $this->_billingInfo = $billingInfo;

        return $this;
    }

    public function getBillingInfo()
    {
        return $this->_billingInfo;
    }

    public function setSession($session)
    {
        $this->_session = $session;

        return $this;
    }

    public function getSession()
    {
        return $this->_session;
    }

    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;

        return $this;
    }

    public function getStoreId()
    {
        return $this->_storeId;
    }

    public function __construct($debugEmail = false)
    {
        $file = new Varien_Io_File();
        $dirName = $file->getDestinationFolder(__FILE__);

        if (strpos($dirName, DS .'Model') !== false) {
            $dir = str_replace(DS .'Model', DS .'certificate', $dirName);
        } else {
            $dir = str_replace(
                DS
                .'includes'
                . DS
                . 'src',
                DS
                . 'app'
                . DS
                . 'code'
                . DS
                . 'community'
                . DS
                . 'TIG'
                . DS
                . 'Buckaroo3Extended'
                . DS
                . 'certificate',
                $dirName
            );
        }

        if (!defined('CERTIFICATE_DIR')) {
            define('CERTIFICATE_DIR', $dir);
        }

        $this->_loadLastOrder();

        if (!Mage::helper('buckaroo3extended')->isAdmin()) {
            $this->setSession(Mage::getSingleton('checkout/session'));
        } else {
            $this->setSession(Mage::getSingleton('core/session'));
        }

        $this->_setOrderBillingInfo();

        if ($debugEmail) {
            $this->setDebugEmail($debugEmail);
        } else {
            $this->setDebugEmail('');
        }

        if (!Mage::helper('buckaroo3extended')->isAdmin() && !Mage::registry('buckaroo_push-error')) {
            $this->_checkExpired();
        }

        if ($this->getOrder()) {
            $this->setStoreId($this->getOrder()->getStoreId());
        } else {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }
    }

    /**
     * Checks if the order object is still there. Prevents errors when session has expired.
     */
    protected function _checkExpired()
    {
        if (empty($this->_order)) {
            $returnUrl = Mage::getUrl(
                Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/failure_redirect', $this->getStoreId())
            );

            Mage::app()->getResponse()->clearHeaders();
            Mage::app()->getResponse()->setRedirect($returnUrl)->sendResponse();
        }
    }

    public function setOrderBillingInfo()
    {
        return $this->_setOrderBillingInfo();
    }

    /**
     * retrieve billing information from order
     *
     */
    protected function _setOrderBillingInfo()
    {
        if (empty($this->_order)) {
            return false;
        }

        $billingAddress = $this->_order->getBillingAddress();

        $firstname          = $billingAddress->getFirstname();
        $middlename         = $billingAddress->getMiddlename();
        $lastname           = $billingAddress->getLastname();
        $city                 = $billingAddress->getCity();
        $state                 = $billingAddress->getState();
        $address          = $billingAddress->getStreetFull();
        $zip              = $billingAddress->getPostcode();
        $email              = $this->_order->getCustomerEmail();
        $telephone          = $billingAddress->getTelephone();
        $fax              = $billingAddress->getFax();
        $countryCode      = $billingAddress->getCountry();

        if (empty($email)) {
            $email = $billingAddress->getEmail();
        }

        $billingInfo = array(
            'firstname'     => $firstname,
            'middlename'    => $middlename,
            'lastname'        => $lastname,
            'city'             => $city,
            'state'         => $state,
            'address'         => $address,
            'zip'             => $zip,
            'email'         => $email,
            'telephone'     => $telephone,
            'fax'             => $fax,
            'countryCode'     => $countryCode
        );

        $this->setBillingInfo($billingInfo);

        return $this;
    }

    /**
     * Restores a previously closed quote so that the cart stays filled after an unsuccessful order
     */
    public function restoreQuote()
    {
        $quoteId = $this->_order->getQuoteId();

        $quote = Mage::getModel('sales/quote')
            ->load($quoteId)
            ->setIsActive(true)
            ->setReservedOrderId(null)
            ->save();
        Mage::getSingleton('checkout/session')->replaceQuote($quote);
    }

    /**
     *  Empties the cart after a successfull order. To prevent the cart from staying filled when the user
     *  has a modified shop that doesn't automatically clear the cart when placing an order.
     */
    public function emptyCart()
    {
        if (!Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/manual_empty_cart', $this->getStoreId())) {
            return false;
        }

        $cartHelper = Mage::helper('checkout/cart');

        $items = $cartHelper->getCart()->getItems();

        foreach ($items as $item) {
            $itemId = $item->getItemId();
            $cartHelper->getCart()->removeItem($itemId);
        }

        $cartHelper->getCart()->save();
    }

    /**
     * Determines the totalamount of the order and the currency to be used based on which currencies are available
     * and which currency the customer has selected.
     *
     * Will default to base currency if the selected currency is unavailable.
     *
     * @return array
     */
    protected function _determineAmountAndCurrency()
    {
        $currentCurrency = Mage::app()->getStore()->getCurrentCurrencyCode();

        if ($this->_order->getOrderCurrencyCode() != $currentCurrency) {
            $currentCurrency = $this->_order->getOrderCurrencyCode();
        }

        // currency is not available for this module
        if ($this->_currentCurrencyIsAllowed()) {
            $currency = $currentCurrency;
            $totalAmount = $this->_order->getGrandTotal();
        } else {
            $totalAmount = $this->_order->getBaseGrandTotal();
            $currency = $this->_order->getBaseCurrency()->getCode();
        }

        return array($currency, $totalAmount);
    }

    /**
     * Check whether the current curreny of the order is allowed
     *
     * @return bool
     */
    protected function _currentCurrencyIsAllowed()
    {
        $code = $this->_order->getPayment()->getMethod();

        $paymentMethod           = null;
        $currenciesAllowedConfig = 'EUR';
        // availability currency codes for this Payment Module
        $methodName = str_replace('buckaroo3extended_', '', $code);
        if ($methodName) {
            $paymentMethod           = Mage::getModel(
                'buckaroo3extended/paymentMethods_' . $methodName . '_paymentMethod'
            );
            $currenciesAllowedConfig = Mage::getStoreConfig(
                'buckaroo/buckaroo3extended_' . $methodName . '/allowed_currencies', $this->getStoreId()
            );
        }

        $currenciesAllowed = array('EUR');
        if ($paymentMethod !== null) {
            $currenciesAllowed = $paymentMethod->allowedCurrencies;
        }

        $currenciesAllowedConfig = explode(',', $currenciesAllowedConfig);

        $currentCurrency = Mage::app()->getStore()->getCurrentCurrencyCode();

        if ($this->_order->getOrderCurrencyCode() != $currentCurrency) {
            $currentCurrency = $this->_order->getOrderCurrencyCode();
        }

        return (in_array($currentCurrency, $currenciesAllowed) && in_array($currentCurrency, $currenciesAllowedConfig));
    }

    /**
     * get locale based on country
     * locale is formatted as language-LOCALE
     *
     * @return array
     */
    protected function _getLocale()
    {
        $country = $this->_order->getBillingAddress()->getCountry();

        $locale = Mage::getStoreConfig('general/locale/code', $this->_order->getStoreId());
        $locale = str_replace('_', '-', $locale);
        $lang = strtoupper(substr($locale, 0, 2));

        return array($country, $locale, $lang);
    }



    /**
     * Retrieves an array with information related to a received response code.
     * This method will only be called when it's child cant find it itself. This list
     * is a general list of known status codes. Its not as inclusive as the lists used\
     * by its children. However, this list also contains general error codes not
     * carried by its children.
     *
     * @return array $returnArray
     */
    protected function _parseResponse()
    {
        $returnArray = array(
            'message' => 'Onbekende responsecode',
            'status'  => self::BUCKAROO_NEUTRAL
        );

        if (!isset($this->_response->Status->Code->Code)) {
            return $returnArray;
        }

        $code = $this->_response->Status->Code->Code;

        if (!isset($this->responseCodes[$code])) {
            $returnArray['message'] .= ': ' . $code;

            return $returnArray;
        }

        $returnArray = $this->responseCodes[$code];
        if (is_object($this->_response)
            && isset($this->_response->Status->SubCode)) {
            //the subcode is additional information sometimes returned by Buckaroo. Currently not used,
            //but it may be of use when debugging.
            $returnArray['subCode'] = array(
                'message' => $this->_response->Status->SubCode->_,
                'code'    => $this->_response->Status->SubCode->Code,
            );
        }

        $returnArray['code'] = $code;

        return $returnArray;
    }

    /**
     * Retrieves an array with information related to a received response code.
     * This method will only be called when it's child cant find it itself. This list
     * is a general list of known status codes. Its not as inclusive as the lists used\
     * by its children. However, this list also contains general error codes not
     *
     * @param $code
     * @return array|bool
     */
    protected function _parsePostResponse($code)
    {
        $isCorrect = $this->_checkCorrectAmount();

        if ($isCorrect !== true) {
            return $isCorrect;
        }

        if (isset($this->responseCodes[$code])) {
            $returnArray = $this->responseCodes[$code];

            if ($this->_response) {
                $returnArray['code'] = $code;
            }

            return $returnArray;
        } elseif (isset($this->oldResponseCodes[$code])) {
            return array(
                'message' => $this->oldResponseCodes[$code]['*']['omschrijving'],
                'status' => $this->oldResponseCodes[$code]['*']['code'],
                'code' => $code
            );
        } else {
            return array(
                'message' => 'Onbekende responsecode: ' . $code,
                'status' => self::BUCKAROO_NEUTRAL,
                'code' => $code,
            );
        }
    }

    /**
     * Checks if the correct amount has been paid.
     */
    protected function _checkCorrectAmount()
    {
        $amountPaid = $this->_postArray['brq_amount'];

        $this->_debugEmail .= 'Currency used is '
                            . $this->_postArray['brq_currency']
                            . '. Order currency is '
                            . $this->_order->getOrderCurrencyCode()
                            . ".\n";

        if ($this->_postArray['brq_currency'] == $this->_order->getOrderCurrencyCode()) {
            $this->_debugEmail .= "Currency used is same as order currency \n";
            $amountOrdered = $this->_order->getGrandTotal();
        } else {
            $this->_debugEmail .= "Currency used is different from order currency \n";
            $amountOrdered = $this->_order->getBaseGrandTotal();
        }

        $this->_debugEmail .= "Amount paid: {$amountPaid}. Amount ordered: {$amountOrdered} \n";

        if (($amountPaid - $amountOrdered) > 0.01 || ($amountPaid - $amountOrdered) < -0.01) {
            return array(
               'message' => 'Incorrect amount transfered',
               'status'  => self::BUCKAROO_INCORRECT_PAYMENT,
            );
        } else {
            return true;
        }
    }

    /**
     * cleans all elements in the array per instructions from Buckaroo PSP
     *
     * @param array $array
     * @return array $cleanArray
     */
    public function _cleanArrayForSoap($array)
    {
        $cleanArray = array();

        foreach ($array as $key => $value) {
            $value = str_replace('\r', ' ', $value);
            $value = str_replace('\n', ' ', $value);
            $cleanArray[$key] = $value;
        }

        return $cleanArray;
    }

    /**
     * function which converts special characters to html numeric equivalents
     */
    public function htmlNumeric($string)
    {
        preg_match_all('/[^\!-\~\s]/', $string, $specialChars);
        if ($specialChars) {
            foreach ($specialChars[0] as $char) {
                $newChar = ord($char);
                $numericChars[] = '&#'.$newChar.';';
                $patterns[] = "/{$char}/";
            }

            if (isset($numericChars) && isset($patterns)) {
                $string = preg_replace($patterns, $numericChars, $string);
            }
        }

        return $string;
    }

    public function log($message, $force = false)
    {
        Mage::helper('buckaroo3extended')->log($message, $force);
    }

    public function logException($e)
    {
        Mage::helper('buckaroo3extended')->logException($e);
    }

    public function sendDebugEmail()
    {
        $debugEmailConfig = Mage::getStoreConfig(
            'buckaroo/buckaroo3extended_advanced/debug_email', $this->getStoreId()
        );
        if (empty($debugEmailConfig)) {
            return;
        }

        $mail = $this->_debugEmail;

        $recipients = explode(
            ',', Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/debug_email', $this->getStoreId())
        );

        foreach ($recipients as $recipient) {
            $mail = new Zend_Mail('utf-8');
            $mail->addTo(trim($recipient));
            $mail->setSubject('Buckaroo 3 Extended Debug Email');
            $mail->setBodyText($mail);
            try {
                $mail->send();
            }
            catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    public function buckarooSort($array)
    {
        $arrayToSort = array();
        $origArray = array();
        foreach ($array as $key => $value) {
            $arrayToSort[strtolower($key)] = $value;
            $origArray[strtolower($key)] = $key;
        }

        ksort($arrayToSort);

        $sortedArray = array();
        foreach ($arrayToSort as $key => $value) {
            $key = $origArray[$key];
            $sortedArray[$key] = $value;
        }

        return $sortedArray;
    }

    protected function _updateRefundedOrderStatus($success = false)
    {
        $successString = $success ? 'success' : 'failed';
        if (!is_object($this->_order)) {
            return $this;
        }

        $state = $this->_order->getState();

        if ($success) {
            $comment = 'Buckaroo refund request was successfully processed.';
        } else {
            $comment = 'Unfortunately the Buckaroo refund request could not be processed succesfully.';
        }

        if ($this->_order->getBaseGrandTotal() != $this->_order->getBaseTotalRefunded()) {
            $configField = "buckaroo/buckaroo3extended_refund/order_status_partial_{$state}_{$successString}";
            $status = Mage::getStoreConfig($configField);
        } else {
            $status = null;
        }

        if (!empty($status)) {
            $this->_order->setStatus($status)->save();
            $this->_order->addStatusHistoryComment($comment, $status)
                 ->save();
        } else {
            $this->_order->addStatusHistoryComment($comment)
                 ->save();
        }
    }

    /**
     * Long list of response codes used by BPE 2.0 gateway. Added here for backwards compatibility. Added
     * to the bottem of the page so it doesn't take up as much space
     */
    //@codingStandardsIgnoreStart
    public $oldResponseCodes = array(
          0     => array( '*'=>array(    "omschrijving" => "De credit card transactie is pending.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "creditcard")),
        001     => array(
            '*'=>array(
                "omschrijving" => "De credit card transactie is pending. De MPI-status van de klant wordt gecheckt.",
                "code"        => self::BUCKAROO_NEUTRAL,
                "type"        => "creditcard"
            )
        ),
        070     => array( '*'=>array(    "omschrijving" => "De refund is nog niet verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "creditcard")),
        071     => array( '*'=>array(    "omschrijving" => "De refund is succesvol verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "creditcard")),
        072     => array( '*'=>array(    "omschrijving" => "Er is een fout opgetreden bij het refunden.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "creditcard")),
        073     => array( '*'=>array(    "omschrijving" => "De refund is geannuleerd.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "creditcard")),
        100     => array( '*'=>array(    "omschrijving" => "De transactie is door de credit-maatschappij goedgekeurd.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "creditcard")),
        101     => array( '*'=>array(    "omschrijving" => "De transactie is door de credit-maatschappij afgekeurd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "creditcard")),
        102     => array(
            '*'=>array(
                "omschrijving" => "De transactie is mislukt. Er is een fout opgetreden in de verwerking bij de creditmaatschappij.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        103     => array(
            '*'=>array(
                "omschrijving" => "Deze creditcardtransactie is niet binnen de maximale,  toegestane tijd uitgevoerd.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        104     => array( '*'=>array(    "omschrijving" => "De kaart is verlopen.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "creditcard")),
        120     => array( '*'=>array(    "omschrijving" => "Deze PayPal transactie is nog niet volledig verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypal")),
        121     => array( '*'=>array(    "omschrijving" => "Transactiestatus: autorisatie geslaagd.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "paypal")),
        122     => array( '*'=>array(    "omschrijving" => "Deze PayPal-transactie is door de consument geannuleerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "paypal")),
        123     => array(
            '*'=>array(
                "omschrijving" => "Deze PayPal-transactie is niet binnen de maximale, toegestane tijd uitgevoerd.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "paypal"
            )
        ),
        124     => array(
            '*'=>array(
                "omschrijving" => "Deze PayPal-transactie is om onbekende reden bij PayPal mislukt.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "paypal")),
        125     => array( '*'=>array(    "omschrijving" => "Deze PayPal-transactie is niet geaccepteerd.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypal")),
        126     => array( '*'=>array(    "omschrijving" => "Deze PayPal-transactie is in afwachting.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypal")),
        135     => array( '*'=>array(    "omschrijving" => "Deze PayPal-transactie is nog niet volledig verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypal")),
        136     => array(
            '*'=>array(
                "omschrijving" => "Om technische reden kon de status van deze transactie nog niet bij PayPal worden achterhaald. De transactie is mogelijk nog niet afgerond",
                "code"        => self::BUCKAROO_NEUTRAL,
                "type"        => "paypal"
            )
        ),
        137     => array( '*'=>array(    "omschrijving" => "De afschrijvingscode is ongeldig.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "paypal")),
        138     => array( '*'=>array(    "omschrijving" => "Er is een systeemfout opgetreden.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "paypal")),
        139     => array( '*'=>array(    "omschrijving" => "Het PayPal transactie-ID is ongeldig of niet beschikbaar.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "paypal")),
        140     => array( '*'=>array(    "omschrijving" => "Er kon geen transactie worden gevonden.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "paypal")),
        150     => array( '*'=>array(    "omschrijving" => "Deze Paysafecard-transactie is nog niet volledig verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypal")),
        151     => array( '*'=>array(    "omschrijving" => "Transactiestatus: authorisatie geslaagd.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "paypal")),
        152     => array(
            '*'=>array(
                "omschrijving" => "Deze Paysafecard-transactie is door de consument geannuleerd.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "paypal"
            )
        ),
        153     => array(
            '*'=>array(
                "omschrijving" => "Deze Paysafecard-transactie is niet binnen de maximale, toegestane tijd uitgevoerd.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "paypal")),
        155     => array( '*'=>array(    "omschrijving" => "Deze Paysafecard-transactie is niet geaccepteerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "paypal")),
        156  => array( '*'=>array(    "omschrijving" => "Deze Paysafecard-transactie is nog niet volledig verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypal")),
        157     => array(
            '*'=>array(
                "omschrijving" => "Om technische reden kon de status van deze transactie nog niet bij Paysafecard worden achterhaald. De transactie is mogelijk nog niet afgerond.",
                "code"        => self::BUCKAROO_NEUTRAL,
                "type"        => "paypal"
            )
        ),
        158     => array(
            '*'=>array(
                "omschrijving" => "Er is een systeemfout opgetreden bij Paysafecard. Onze excuses voor het ongemak.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "paypal"
            )
        ),
        159     => array(
            '*'=>array(
                "omschrijving" => "Het Paysafecard transactie-id is ongeldig of niet beschikbaar.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "paypal"
            )
        ),
        170     => array( '*'=>array(    "omschrijving" => "Deze Cash-Ticket transactie is nog niet volledig verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypal")),
        171     => array( '*'=>array(    "omschrijving" => "Transactiestatus: authorisatie geslaagd",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "creditcard")),
        172     => array(
            '*'=>array(
                "omschrijving" => "Deze Cash-Ticket transactie is door de consument geannuleerd.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        173     => array(
            '*'=>array(
                "omschrijving" => "Deze Cash-Ticket transactie is niet binnen de maximale, toegestane tijd uitgevoerd.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        175     => array( '*'=>array(    "omschrijving" => "Deze Cash-Ticket transactie is niet geaccepteerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "creditcard")),
        176  => array( '*'=>array(    "omschrijving" => "Deze Cash-Ticket transactie is nog niet volledig verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "creditcard")),
        177  => array(
            '*'=>array(
                "omschrijving" => "Om technische reden kon de status van deze transactie nog niet bij Cash-Ticket worden achterhaald. De transactie is mogelijk nog niet afgerond.",
                "code"        => self::BUCKAROO_NEUTRAL,
                "type"        => "creditcard"
            )
        ),
        178  => array(
            '*'=>array(
                "omschrijving" => "Er is een systeemfout opgetreden bij Cash-Ticket. Onze excuses voor het ongemak.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        179  => array(
            '*'=>array(
                "omschrijving" => "Het Cash-Ticket transactie-id is ongeldig of niet beschikbaar.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        201     => array(
            '*'=>array(
                "omschrijving" => "Er is timeout opgetreden bij het verwerken van de transactie.Gebruik de TransactionKey om de verwerkingsstatus nogmaals te controleren.",
                "code"        => self::BUCKAROO_NEUTRAL,
                "type"        => "creditcard"
            )
        ),
        203     => array(
            '*'=>array(
                "omschrijving" => "De transactie is geweigerd. Het creditcardnummer is geblokkeerd.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        204     => array( '*'=>array(    "omschrijving" => "De transactie is geweigerd. Het ip-adres is geblokkeerd",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "creditcard")),
        205     => array(
            '*'=>array(
                "omschrijving" => "De transactie is geweigerd. Het land van uitgifte van deze creditcard is geblokkeerd",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        206     => array(
            '*'=>array(
                "omschrijving" => "De transactie is geweigerd. De faktuur [waarde] wordt momenteel of is reeds betaald.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        207     => array(
            '*'=>array(
                "omschrijving" => "De transactie is geweigerd. Het maximaal aantal betaalpogingen voor faktuur [waarde] is overschreden.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "creditcard"
            )
        ),
        242  => array( '*'=>array(  "omschrijving" => "Provisie BetaalGarant succesvol verwerkt.",
                        "code" => self::BUCKAROO_SUCCESS,
                        "type" => "garant")),
        243  => array( '*'=>array(  "omschrijving" => "Provisie incassobureau BetaalGarant succesvol verwerkt.",
                        "code" => self::BUCKAROO_SUCCESS,
                        "type" => "garant")),
        244  => array( '*'=>array(  "omschrijving" => "Provisie Buckaroo BetaalGarant succesvol verwerkt.",
                        "code" => self::BUCKAROO_SUCCESS,
                        "type" => "garant")),
        245  => array( '*'=>array(  "omschrijving" => "Toetskosten incassobureau BetaalGarant succesvol verwerkt.",
                        "code" => self::BUCKAROO_SUCCESS,
                        "type" => "garant")),
        246  => array( '*'=>array(  "omschrijving" => "Btw incassobureau BetaalGarant succesvol verwerkt.",
                        "code" => self::BUCKAROO_SUCCESS,
                        "type" => "garant")),
        247  => array( '*'=>array(  "omschrijving" => "Btw buckaroo BetaalGarant succesvol verwerkt.",
                        "code" => self::BUCKAROO_SUCCESS,
                        "type" => "garant")),
        252     => array( '*'=>array(    "omschrijving" => "Kredietwaardigheidcontrole resultaat negatief.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "garant")),
        254  => array( '*'=>array(  "omschrijving" => "Betaalgarant verzoek succesvol verwerkt.",
                        "code" => self::BUCKAROO_SUCCESS,
                        "type" => "garant")),
        260     => array( '*'=>array(    "omschrijving" => "Kredietwaardigheidcontrole abonnement niet actief.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "garant")),
        261     => array(
            '*'=>array(
                "omschrijving" => "Technische fout opgetreden tijdens kredietwaardigheidcontrole.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "garant"
            )
        ),
        262     => array(
            '*'=>array(
                "omschrijving" => "Verplichte velden voor kredietwaardigheidcontrole ontbreken of zijn onjuist",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "garant"
            )
        ),
        300     => array( '*'=>array("omschrijving" => "Betaling voor deze overschrijving wordt nog verwacht.",
                                 "code" => self::BUCKAROO_NEUTRAL,
                                 "type" => "transfer"),
                       'buckarootransfergarant'=>array("omschrijving" => "Uw bestelling is geaccepteerd.",
                                                          "code" => self::BUCKAROO_SUCCESS,
                                                        "type" => "transfer")),
        301     => array( '*'=>array(    "omschrijving" => "De overschrijving is ontvangen.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "transfer")),
        302     => array( '*'=>array(    "omschrijving" => "De transactie is geweigerd of afgewezen.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "transfer")),
        303     => array(
            '*'=>array(
                "omschrijving" => "De uiterste betaaldatum voor deze overschrijving is verstreken.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "transfer"
            )
        ),
        304     => array( '*'=>array(    "omschrijving" => "De datum voor ingebrekestelling is verstreken.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "transfer")),
        305     => array(
            '*'=>array(
                "omschrijving" => "Het ontvangen bedrag voor de overschrijving is lager dan het bedrag van de transactie.",
                "code"        => 'BUCKAROO_INCORRECT_AMOUNT',
                "type"        => "transfer"
            )
        ),
        306     => array(
            '*'=>array(
                "omschrijving" => "Het ontvangen bedrag voor de overschrijving is groter dan het bedrag van de transactie.",
                "code"        => 'BUCKAROO_INCORRECT_AMOUNT',
                "type"        => "transfer"
            )
        ),
        309     => array( '*'=>array(    "omschrijving" => "De overschrijving is geannuleerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "transfer")),
        345  => array( '*'=>array(    "omschrijving" => "Oorspronkelijk transactie-bedrag gedeeld.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "transfer")),
        371     => array( '*'=>array(    "omschrijving" => "De refund voor deze overschrijving is verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "creditcard")),
        372     => array( '*'=>array(    "omschrijving" => "De refund voor deze overschrijving is verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "transfer")),
        373     => array( '*'=>array(    "omschrijving" => "De refund voor deze overschrijving is verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "transfer")),
        381     => array( '*'=>array(    "omschrijving" => "De refund voor deze overschrijving is mislukt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "transfer")),
        382     => array( '*'=>array(    "omschrijving" => "De refund voor deze overschrijving is mislukt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "transfer")),
        383     => array( '*'=>array(    "omschrijving" => "De refund voor deze overschrijving is mislukt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "transfer")),
        390     => array(
            '*'=>array(
                "omschrijving" => "De transactie is buiten Buckaroo om met de klant afgehandeld.",
                "code"        => self::BUCKAROO_SUCCESS,
                "type"        => "transfer"
            )
        ),
        392     => array( '*'=>array(    "omschrijving" => "Anders betaald.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "transfer")),
        400     => array( '*'=>array(    "omschrijving" => "De kadokaart-transactie is nog in behandeling",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "giftcard")),
        401     => array( '*'=>array(    "omschrijving" => "De betaling middels kado-kaart is geslaagd.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "giftcard")),
        402     => array( '*'=>array(    "omschrijving" => "Betaling middels de kadokaart is afgewezen.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "giftcard")),
        409     => array( '*'=>array(    "omschrijving" => "Betaling middels de kadokaart is geannuleerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "giftcard")),
        403     => array(
            '*'=>array(
                "omschrijving" => "Deze Giftcard transactie is niet binnen de maximale, toegestane tijd uitgevoerd.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "giftcard"
            )
        ),
        404     => array(
            '*'=>array(
                "omschrijving" => "Deze Giftcard transactie is om onbekende reden bij de kaartuitgever mislukt.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "giftcard"
            )
        ),
        409     => array( '*'=>array(    "omschrijving" => "Betaling middels de kadokaart is geannuleerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "giftcard")),
        410     => array( '*'=>array(    "omschrijving" => "De Merchant Account Code is ongeldig",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "giftcard")),
        411     => array( '*'=>array(    "omschrijving" => "De betaling middels kadokaart is voorlopig geaccepteerd.",
                        "code"        => 'BUCKAROO_PENDINGPAYMENT',
                        "type"        => "giftcard")),
        414     => array( '*'=>array(    "omschrijving" => "Er is een systeem-fout opgetreden.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "giftcard")),
        421     => array( '*'=>array(    "omschrijving" => "Er is een onbekende Issuer voor de kado-kaart opgegeven.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "giftcard")),
        422     => array(
            '*'=>array(
                "omschrijving" => "Er is een fout opgetreden bij de Issuer. De betaling is mislukt. [waarde].",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "giftcard"
            )
        ),
        425     => array( '*'=>array(    "omschrijving" => "Niet genoeg saldo om deze transactie uit te voeren.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "giftcard")),
        461  => array( '*'=>array(    "omschrijving" => "Transactie voltooid.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "giftcard")),
        462  => array( '*'=>array(    "omschrijving" => "Transactie voltooid.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "giftcard")),
        463  => array( '*'=>array(    "omschrijving" => "Transactie voltooid.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "giftcard")),
        464  => array( '*'=>array(    "omschrijving" => "Transactie voltooid.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "giftcard")),
        468  => array( '*'=>array(    "omschrijving" => "Originele factuur voor deze vordering niet gevonden.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "giftcard")),
        471     => array( '*'=>array(    "omschrijving" => "De refund voor deze giftcardbetaling is verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "giftcard")),
        472     => array( '*'=>array(    "omschrijving" => "De refund voor deze giftcardbetaling is mislukt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "giftcard")),
        500     => array( '*'=>array(    "omschrijving" => "Paypermail: transactie pending",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypermail")),
        541     => array( '*'=>array(    "omschrijving" => "Transactiekosten zijn verrekend met saldo.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypermail")),
        550     => array( '*'=>array(    "omschrijving" => "De uitbetaling is nog niet verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypermail")),
        551     => array( '*'=>array(    "omschrijving" => "De uitbetaling is succesvol verwerkt.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "paypermail")),
        552     => array( '*'=>array(    "omschrijving" => "Transactiekosten zijn verrekend met saldo.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypermail")),
        553  => array( '*'=>array(    "omschrijving" => "Transactiekosten zijn verrekend met saldo.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypermail")),
        560     => array( '*'=>array(    "omschrijving" => "Correctiebetaling uitgevoerd door Buckaroo.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypermail")),
        581     => array( '*'=>array(    "omschrijving" => "Overschrijving van of naar ander Buckaroo-account.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "paypermail")),
        600     => array( '*'=>array(    "omschrijving" => "Eenmalige machtiging is nog niet verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "collect")),
        601     => array( '*'=>array(    "omschrijving" => "Eenmalige machtiging is met succes verwerkt.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "collect")),
        602     => array( '*'=>array(    "omschrijving" => "Eenmalige machtiging is door de bank afgewezen.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "collect")),
        605     => array( '*'=>array(    "omschrijving" => "Eenmalige machtiging is gestorneerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "collect")),
        609     => array(
            '*'=>array(
                "omschrijving" => "Eenmalige machtiging is geannuleerd voordat incasso plaatsvond.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "collect"
            )
        ),
        610     => array(
            '*'=>array(
                "omschrijving" => "Eenmalige machtiging is door de bank afgewezen. Rekening ongeldig.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "collect"
            )
        ),
        612     => array( '*'=>array(    "omschrijving" => "Terugboeking wegens Melding Onterechte Incasso",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "collect")),
        671     => array( '*'=>array(    "omschrijving" => "De refund voor deze machtiging is verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "collect")),
        672     => array( '*'=>array(    "omschrijving" => "De refund voor deze machtiging is mislukt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "collect")),
        700     => array(
            '*'=>array(
                "omschrijving" => "De betaalopdracht is geaccepteerd en wordt in behandeling genomen.",
                "code"        => self::BUCKAROO_NEUTRAL,
                "type"        => "batch"
            )
        ),
        701     => array( '*'=>array(    "omschrijving" => "De betaalopdracht is verwerkt.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "batch")),
        702     => array(
            '*'=>array(
                "omschrijving" => "De betaalopdracht is door de bank teruggestort wegens incorrecte rekeninggegevens.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "batch"
            )
        ),
        703  => array( '*'=>array(    "omschrijving" => "De betaalopdracht is afgewezen door Buckaroo.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "batch")),
        704  => array( '*'=>array(    "omschrijving" => "Betaalopdracht geannuleerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "batch")),
        705     => array( '*'=>array(    "omschrijving" => "De batch kon niet worden ingepland. Error: [waarde]",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "batch")),
        710     => array( '*'=>array(    "omschrijving" => "Betaalopdracht nog niet geverifieerd.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "batch")),
        711     => array( '*'=>array(    "omschrijving" => "De batch kon niet gevonden worden: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "batch")),
        712     => array( '*'=>array(    "omschrijving" => "De batch is reeds verwerkt: [waarde].",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "batch")),
        720     => array( '*'=>array(    "omschrijving" => "Er is voor deze batch-transactie geen klant-id opgegeven.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "batch")),
        721     => array( '*'=>array(    "omschrijving" => "Het opgegeven klant-id kon niet worden gevonden.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "batch")),
        800     => array( '*'=>array(    "omschrijving" => "Deze iDeal-transactie is nog niet volledig verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "ideal")),
        801     => array( '*'=>array(    "omschrijving" => "Deze iDeal-transactie is met succes verwerkt.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "ideal")),
        802     => array(
            '*'=>array(
                "omschrijving" => "Deze iDeal-transactie is door de consument geannuleerd. Trx: [waarde]",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "ideal"
            )
        ),
        803     => array(
            '*'=>array(
                "omschrijving" => "Deze iDeal-transactie is niet binnen de maximale toegestane tijd uitgevoerd. Trx: [waarde]",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "ideal"
            )
        ),
        804     => array(
            '*'=>array(
                "omschrijving" => "Deze iDeal-transactie is om onbekende reden bij de bank mislukt. Trx: [waarde]",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "ideal"
            )
        ),
        810     => array( '*'=>array(    "omschrijving" => "Issuer (bank) is onbekend: [waarde]",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "ideal")),
        811     => array(
            '*'=>array(
                "omschrijving" => "Om technische reden kon de status van deze transactie nog niet bij de bank worden achterhaald. De transactie is nog niet afgerond.",
                "code"        => self::BUCKAROO_NEUTRAL,
                "type"        => "ideal"
            )
        ),
        812     => array( '*'=>array(    "omschrijving" => "De entrance-code [waarde] is ongeldig.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "ideal")),
        813     => array( '*'=>array(    "omschrijving" => "Acquirer-code is onbekend: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "ideal")),
        814     => array(
            '*'=>array(
                "omschrijving" => "Er is een systeemfout opgetreden. We zullen deze zo snel mogelijk verhelpen. De status zal daarna worden herzien.",
                "code"        => self::BUCKAROO_NEUTRAL,
                "type"        => "ideal"
            )
        ),
        815     => array(
            '*'=>array(
                "omschrijving" => "Op dit moment is de betaalmethode iDEAL niet beschikbaar wegens een storing bij de bank.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "ideal"
            )
        ),
        816     => array( '*'=>array(    "omschrijving" => "Er kon geen transactie worden gevonden. Criteria: [waarde]",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "ideal")),
        820     => array( '*'=>array(    "omschrijving" => "Deze Giropay-transactie is nog niet volledig verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "ideal")),
        821     => array( '*'=>array(    "omschrijving" => "Deze Giropay-transactie is met succes verwerkt.",
                        "code"        => self::BUCKAROO_SUCCESS,
                        "type"        => "ideal")),
        822     => array(
            '*'=>array(
                "omschrijving" => "Deze Giropay-transactie is door de consument geannuleerd. Trx: [waarde]",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "ideal"
            )
        ),
        823     => array(
            '*'=>array(
                "omschrijving" => "Deze Giropay-transactie is niet binnen de maximale toegestane tijd uitgevoerd. Trx: [waarde]",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "ideal"
            )
        ),
        824     => array( '*'=>array(    "omschrijving" => "Deze Giropay-transactie is door de bank afgewezen.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "ideal")),
        830     => array( '*'=>array(    "omschrijving" => "Issuer (bankleitzahl) is onbekend: [waarde]",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "ideal")),
        831     => array(
            '*'=>array(
                "omschrijving" => "Om technische reden kon de status van deze transactie nog niet bij de bank worden achterhaald. De transactie is nog niet afgerond.",
                "code"        => self::BUCKAROO_NEUTRAL,
                "type"        => "ideal"
            )
        ),
        833     => array( '*'=>array(    "omschrijving" => "De entrance-code [waarde] is ongeldig.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "ideal")),
        834     => array(
            '*'=>array(
                "omschrijving" => "Er is een systeemfout opgetreden. We zullen deze zo snel mogelijk verhelpen. De status zal daarna worden herzien.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "ideal"
            )
        ),
        835     => array( '*'=>array(    "omschrijving" => "Het Giropay transactie-id is ongeldig of niet beschikbaar.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "ideal")),
        836     => array( '*'=>array(    "omschrijving" => "Er kon geen transactie worden gevonden. Criteria: [waarde]",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "ideal")),
        871     => array( '*'=>array(    "omschrijving" => "De refund voor deze iDeal-cardbetaling is verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "ideal")),
        872     => array( '*'=>array(    "omschrijving" => "De refund voor deze iDeal-cardbetaling is mislukt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "ideal")),
        873     => array( '*'=>array(    "omschrijving" => "De refund voor deze GiroPay-cardbetaling is verwerkt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "ideal")),
        874     => array( '*'=>array(    "omschrijving" => "De refund voor deze GiroPay-betaling is mislukt.",
                        "code"        => self::BUCKAROO_NEUTRAL,
                        "type"        => "ideal")),
        900     => array( '*'=>array(    "omschrijving" => "Geen XML-bericht ontvangen.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        901     => array( '*'=>array(    "omschrijving" => "Ongeldig XML-bericht.  [waarde]",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        910     => array( '*'=>array(    "omschrijving" => "0 EUR transactie, Customergegevens opgeslagen.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        931     => array( '*'=>array(    "omschrijving" => "[nodetype] [element] ontbreekt.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        932     => array( '*'=>array(    "omschrijving" => "Teveel elementen type [element] (max. 1).",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        933     => array( '*'=>array(    "omschrijving" => "Waarde [nodetype] [element] ontbreekt.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        934     => array(
            '*'=>array(
                "omschrijving" => "Waarde [nodetype] [element] (occurance [occurance]) ontbreekt.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "xml"
            )
        ),
        935     => array(
            '*'=>array(
                "omschrijving" => "Waarde attribuut [attribuut] ontbreekt in element [element].",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "xml"
            )
        ),
        940     => array( '*'=>array(    "omschrijving" => "Ongeldig request: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        941     => array( '*'=>array(    "omschrijving" => "Waarde veld [veld] ongeldig: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        942     => array( '*'=>array(    "omschrijving" => "Waarde attribuut [veld] ongeldig: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        943     => array( '*'=>array(    "omschrijving" => "Creditcard-type onbekend: [waarde]. (mastercard of visa)",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        944     => array( '*'=>array(    "omschrijving" => "Kaartnummer ongeldig (Luhn-check): [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        945     => array( '*'=>array(    "omschrijving" => "Valuta onbekend ongeldig: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        946     => array( '*'=>array(    "omschrijving" => "Bedrag is geen numerieke waarde: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        947     => array( '*'=>array(    "omschrijving" => "Bedrag ongeldig: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        948     => array( '*'=>array(    "omschrijving" => "CVC-code ongeldig: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        949     => array( '*'=>array(    "omschrijving" => "Maand geldigheidsduur creditcard ongeldig: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        950     => array( '*'=>array(    "omschrijving" => "Jaar geldigheidsduur creditcard ongeldig: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        951     => array( '*'=>array(    "omschrijving" => "Taal onbekend of niet ondersteund: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        952     => array( '*'=>array(    "omschrijving" => "Het factuurnummer ontbreekt. Dit veld is verplicht.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        953     => array( '*'=>array(    "omschrijving" => "Geblokkeerd door velocitycheck",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        954     => array( '*'=>array(    "omschrijving" => "Het transactie-ID [waarde] is al in gebruik.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        955     => array( '*'=>array(    "omschrijving" => "Authenticatie voor deze creditcard betaling is afgewezen",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        956     => array(
            '*'=>array(
                "omschrijving" => "De enrolled status van de creditcard kon niet achterhaald worden.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "xml"
            )
        ),
        960     => array( '*'=>array(    "omschrijving" => "Klantnummer ongeldig: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        961     => array( '*'=>array(    "omschrijving" => "Creditcard-type niet geactiveerd: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        962     => array( '*'=>array(    "omschrijving" => "Gekozen valuta ongeldig voor Merchant: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        963     => array( '*'=>array(    "omschrijving" => "Het transactie-id is ongeldig: [waarde]",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        964     => array( '*'=>array(    "omschrijving" => "Er zijn geen betaalmethoden geactiveerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        971  => array( '*'=>array(    "omschrijving" => "Er is geen naam opgegeven.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        972  => array( '*'=>array(    "omschrijving" => "Er is geen adres opgegeven.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        973  => array( '*'=>array(    "omschrijving" => "Er is geen postcode ingevuld.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        974  => array( '*'=>array(    "omschrijving" => "Er is geen plaats ingevuld.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        975  => array( '*'=>array(    "omschrijving" => "Er is geen land ingevuld.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        976  => array( '*'=>array(    "omschrijving" => "Er is geen geslacht ingevuld.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        977  => array( '*'=>array(    "omschrijving" => "Mailadres ongeldig.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        978     => array( '*'=>array(    "omschrijving" => "De XML koppeling voor creditcards is nog niet geactiveerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        980     => array( '*'=>array(    "omschrijving" => "De betaalmethode [waarde] is niet geactiveerd.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        981     => array( '*'=>array(    "omschrijving" => "De datum van het abonnement is geen geldige datum.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        982  => array( '*'=>array(    "omschrijving" => "Het abonnement is nog niet ingegaan.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        983     => array( '*'=>array(    "omschrijving" => "Het abonnement is verlopen.",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        990     => array( '*'=>array(    "omschrijving" => "De digitale handtekening is incorrect: [waarde].",
                        "code"        => self::BUCKAROO_FAILED,
                        "type"        => "xml")),
        991     => array(
            '*'=>array(
                "omschrijving" => "Er is een fout opgetreden bij het verwerken van de transactie. De Merchant Account Code kon niet worden gelocaliseerd.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "xml"
            )
        ),
        992     => array(
            '*'=>array(
                "omschrijving" => "Er is fout opgetreden bij het verwerken van de response. We zullen de storing zo snel mogelijk verhelpen.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "xml"
            )
        ),
        993     => array(
            '*'=>array(
                "omschrijving" => "Er is een fout opgetreden bij het verwerken van de transactie. We zullen de storing zo snel mogelijk verhelpen.",
                "code"        => self::BUCKAROO_FAILED,
                "type"        => "xml"
            )
        ),
        999     => array(
            '*'=>array(
                "omschrijving" => "Er is een fout opgetreden waarvan de oorzaak vooralsnog onbekend is. We zullen de storing zo snel mogelijk verhelpen.",
                "code"        => self::BUCKAROO_FAILED
            )
        )
    );
    //@codingStandardsIgnoreEnd
}
