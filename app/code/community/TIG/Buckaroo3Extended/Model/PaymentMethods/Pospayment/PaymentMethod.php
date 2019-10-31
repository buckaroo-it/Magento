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
class TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_PaymentMethod extends TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod
{
    const POSPAYMENT_XHEADER = 'Pos-Terminal-Id';
    const POSPAYMENT_COOKIE = 'Pos-Terminal-Id';

    public $allowedCurrencies = array(
        'EUR',
    );

    protected $_code = 'buckaroo3extended_pospayment';

    protected $_canOrder                = true;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;

    /**
     * POSPayment may only be used when the terminalid is set in the header or cookie.
     * If an User-Agent is configured, that one will have to match with that of the client as well.
     *
     * {@inheritdoc}
     */
    public function isAvailable($quote = null)
    {
        $terminalId = $this->getPosPaymentTerminalId();

        if (strlen($terminalId) <= 0) {
            return false;
        }

        $storeId = Mage::app()->getStore()->getId();
        $userAgent = Mage::app()->getRequest()->getHeader('User-Agent');
        $userAgentConfiguration = trim(Mage::getStoreConfig('buckaroo/' . $this->_code . '/user_agent', $storeId));

        if (strlen($userAgentConfiguration) > 0 && $userAgent != $userAgentConfiguration) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}
