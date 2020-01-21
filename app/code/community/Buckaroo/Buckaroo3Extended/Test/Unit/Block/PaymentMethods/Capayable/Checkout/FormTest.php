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
class Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_FormTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_Form */
    protected $_instance = null;

    /** @var Mage_Sales_Model_Quote|PHPUnit_Framework_MockObject_MockObject */
    protected $_quoteMock;

    public function setUp()
    {
        $this->registerMockSessions('checkout');

        $this->_quoteMock = $this->getMockBuilder('Mage_Sales_Model_Quote')->disableOriginalConstructor()->getMock();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('getQuote')->willReturn($this->_quoteMock);
    }

    /**
     * @return Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_Form
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Buckaroo_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_Form();
        }

        return $this->_instance;
    }

    public function testGetOrderAs()
    {
        $paymentmethodMock = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod')
            ->getMock();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('getData')->with('_BPE_OrderAs')->willReturn('Company');

        $instance = $this->_getInstance();
        $instance->setMethod($paymentmethodMock);
        $result = $instance->getOrderAs();
        $this->assertEquals('Company', $result);
    }

    public function testGetCompanyCOCRegistration()
    {
        $paymentmethodMock = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod')
            ->getMock();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('getData')->with('_BPE_CompanyCOCRegistration')->willReturn('123456789');

        $instance = $this->_getInstance();
        $instance->setMethod($paymentmethodMock);
        $result = $instance->getCompanyCOCRegistration();
        $this->assertEquals('123456789', $result);
    }

    public function testGetCompanyName()
    {
        $paymentmethodMock = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod')
            ->getMock();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('getData')->with('_BPE_CompanyName')->willReturn('Buckaroo');

        $instance = $this->_getInstance();
        $instance->setMethod($paymentmethodMock);
        $result = $instance->getCompanyName();
        $this->assertEquals('Buckaroo', $result);
    }
}
