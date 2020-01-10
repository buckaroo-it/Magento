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
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Kbc_PaymentMethodTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_Kbc_PaymentMethod */
    protected $_instance = null;

    /**
     * @return TIG_Buckaroo3Extended_Model_PaymentMethods_Kbc_PaymentMethod
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_Kbc_PaymentMethod();
        }

        return $this->_instance;
    }

    public function testGetAllowedCurrencies()
    {
        $instance = $this->_getInstance();
        $result = $instance->getAllowedCurrencies();

        $this->assertInternalType('array', $result);
        $this->assertContains('EUR', $result);
    }

    public function testGetCode()
    {
        $instance = $this->_getInstance();
        $result = $instance->getCode();

        $this->assertEquals('buckaroo3extended_kbc', $result);
    }

    public function testIsAvailable()
    {
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended_kbc/active', 1);
        Mage::app()->getStore()->setConfig('payment/buckaroo3extended_kbc/active', 1);
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended/key', 1);
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended/thumbprint', 1);

        $quoteMock = $this->getMockBuilder('Mage_Sales_Model_Quote')
            ->setMethods(array('getBaseGrandTotal'))
            ->getMock();
        $quoteMock->expects($this->any())->method('getBaseGrandTotal')->willReturn(1);

        $instance = $this->_getInstance();
        $result = $instance->isAvailable($quoteMock);

        $this->assertEquals(true, $result);
    }
}
