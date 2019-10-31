<?php
class TIG_Buckaroo3Extended_Model_Request_Availability extends TIG_Buckaroo3Extended_Model_Abstract
{
    public static $allowedCurrencies = array(
        'EUR',
        'GBP',
        'USD',
        'CAD',
        'SHR',
        'NOK',
        'SEK',
        'DKK',
        'ARS',
        'BRL',
        'HRK',
        'LTL',
        'TRY',
        'TRL',
        'AUD',
        'CNY',
        'LVL',
        'MXN',
        'MXP',
        'PLN',
        'CHF',
        'CZK',
    );

    /**
     * Various checks to determine if Buckaroo payment options should be available to customers
     *
     * @param null $quote
     * @return bool
     */
    public static function canUseBuckaroo($quote = null)
    {
        $return = false;

        $configValues    = self::_checkConfigValues($quote);

        $currencyAllowed = self::_checkCurrencyAllowed();

        $ipAllowed       = self::_checkIpAllowed();

        $isZeroPayment   = self::_checkGrandTotalNotZero($quote);

        $isEnterprise    = self::isEnterprise();

        if ($configValues        === true
            && $currencyAllowed  === true
            && $ipAllowed        === true
            && (
                $isZeroPayment   === false || $isEnterprise
                )
        )
        {
            $return = true;
        }

        return $return;
    }

    /**
     * If we are using enterprise version or not
     *
     * @return int
     */
    public static function isEnterprise()
    {
        return (int) is_object(Mage::getConfig()->getNode('global/models/enterprise_enterprise'));
    }

    /**
     * Checks if all required configuration options are set
     * NOTE: does not check if entered values are valid, only that they're not empty
     *
     * @return bool
     */
    private static function _checkConfigValues($quote = null)
    {
        $configValues = false;

        $storeId = Mage::app()->getStore()->getStoreId();
        // get via quote the store id for admin
        if ('Admin' == Mage::app()->getStore()->getName() && $quote) {
            $storeId = $quote->getStoreId();
        }

        //config values that need to be entered
        $configEnabled             = (bool) Mage::getStoreConfig('buckaroo/buckaroo3extended/active', $storeId);
        $merchantKeyEntered        = (bool) Mage::getStoreConfig('buckaroo/buckaroo3extended/key', $storeId);
        $thumbprintEntered         = (bool) Mage::getStoreConfig('buckaroo/buckaroo3extended/thumbprint', $storeId);
        $orderStatusSuccessEntered = (bool) Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/order_status_success', $storeId);
        $orderStatusFailedEntered  = (bool) Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/order_status_failed', $storeId);

        //advanced config values that need to be entered
        $newOrderStatusEntered     = (bool) Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/order_status', $storeId);
        $orderStateSuccessEntered  = (bool) Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/order_state_success', $storeId);
        $orderStateFailedEntered   = (bool) Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/order_state_failed', $storeId);

        if ($configEnabled
            && $merchantKeyEntered
            && $thumbprintEntered
            && $orderStatusSuccessEntered
            && $orderStatusFailedEntered
            && $newOrderStatusEntered
            && $orderStateSuccessEntered
            && $orderStateFailedEntered
        )
        {
            $configValues = true;
        }

        return $configValues;
    }

    /**
     * Checks if the store's base currency is allowed by Buckaroo
     *
     * @return bool
     */
    private static function _checkCurrencyAllowed()
    {
        $allowed = false;

        $baseCurrency = Mage::app()->getStore()->getBaseCurrency()->getCode();

        if (in_array($baseCurrency, self::$allowedCurrencies)) {
            $allowed = true;
        }

        return $allowed;
    }

    /**
     * If the 'limit by IP' options is set in the backend, check if the user's IP ids allowed
     * NOTE: this is only the general limit by ip option. Individual module's limit by IP options are not checked here
     *
     * @return bool
     */
    private static function _checkIpAllowed()
    {
        $ipAllowed = false;

        if (Mage::getStoreConfig('dev/restrict/allow_ips') && Mage::getStoreConfig('buckaroo/buckaroo3extended_advanced/limit_by_ip'))
        {
            $allowedIp = explode(',', mage::getStoreConfig('dev/restrict/allow_ips'));
            if (in_array(Mage::helper('core/http')->getRemoteAddr(), $allowedIp))
            {
                $ipAllowed = true;
            }
        } else {
            $ipAllowed = true;
        }

        return $ipAllowed;
    }

    /**
     * Checks if the order base grandtotal is zero.
     * NOTE: this check is currently not used. Will be implemented later when I know for certain which payment methods can and cannot handle
     * zero-grandtotal payments.
     *
     * @param $quote
     * @return bool
     */
    private static function _checkGrandTotalNotZero($quote)
    {
        if (empty($quote)) {
            return true;
        }

        $isZero = false;

        if ($quote->getBaseGrandTotal() < 0.01) {
            $isZero = true;
        }

        return $isZero;
    }
}
