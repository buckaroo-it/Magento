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
class Buckaroo_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Payconiq_PaymentMethodTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_PaymentMethod */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions('core');
    }

    /**
     * @return null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_PaymentMethod
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_PaymentMethod();
        }

        return $this->_instance;
    }

    public function testGetFormBlockType()
    {
        $instance = $this->_getInstance();
        $result = $instance->getFormBlockType();
        $this->assertEquals('buckaroo3extended/paymentMethods_payconiq_checkout_form', $result);
    }

    public function testGetAllowedCurrencies()
    {
        $expectedOptions = array(
            'AUD', 'BRL', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'JPY',
            'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'USD'
        );

        $instance = $this->_getInstance();
        $result = $instance->getAllowedCurrencies();
        $this->assertEquals($expectedOptions, $result);
    }

    public function testGetCode()
    {
        $instance = $this->_getInstance();
        $result = $instance->getCode();
        $this->assertEquals('buckaroo3extended_payconiq', $result);
    }

    public function testGetOrderPlaceRedirectUrl()
    {
        $instance = $this->_getInstance();
        $result = $instance->getOrderPlaceRedirectUrl();
        $this->assertContains('buckaroo3extended/payconiq/checkout/', $result);
    }
}
