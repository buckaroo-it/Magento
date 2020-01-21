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
class Buckaroo_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_P24_PaymentMethodTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_P24_PaymentMethod */
    protected $_instance = null;

    /**
     * @return Buckaroo_Buckaroo3Extended_Model_PaymentMethods_P24_PaymentMethod
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Buckaroo_Buckaroo3Extended_Model_PaymentMethods_P24_PaymentMethod();
        }

        return $this->_instance;
    }

    public function testGetAllowedCurrencies()
    {
        $instance = $this->_getInstance();
        $result = $instance->getAllowedCurrencies();

        $this->assertInternalType('array', $result);
        $this->assertContains('PLN', $result);
        $this->assertNotContains('EUR', $result);
    }

    public function testGetCode()
    {
        $instance = $this->_getInstance();
        $result = $instance->getCode();

        $this->assertEquals('buckaroo3extended_p24', $result);
    }

    public function testIsAvailable()
    {
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended_p24/active', 1);
        Mage::app()->getStore()->setConfig('payment/buckaroo3extended_p24/active', 1);
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended/key', 1);
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended/thumbprint', 1);

        $quoteMock = $this->getMockBuilder('Mage_Sales_Model_Quote')
            ->setMethods(array('getBaseGrandTotal', 'getQuoteCurrencyCode'))
            ->getMock();
        $quoteMock->expects($this->any())->method('getBaseGrandTotal')->willReturn(1);
        $quoteMock->expects($this->once())->method('getQuoteCurrencyCode')->willReturn('PLN');

        $instance = $this->_getInstance();
        $result   = $instance->isAvailable($quoteMock);

        $this->assertEquals(true, $result);

    }
}
