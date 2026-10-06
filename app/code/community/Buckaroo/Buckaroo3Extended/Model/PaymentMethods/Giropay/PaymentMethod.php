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
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */

/**
 * Giropay is no longer offered at checkout. The class stays so orders that were paid with it can still be opened,
 * invoiced and credited.
 */
class Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Giropay_PaymentMethod extends Buckaroo_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_giropay';

    public function isAvailable($quote = null)
    {
        return false;
    }

    public function canUseCheckout()
    {
        return false;
    }

    public function canUseInternal()
    {
        return false;
    }

    public function canUseForMultishipping()
    {
        return false;
    }
}
