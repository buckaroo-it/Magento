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
class TIG_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_FormTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_Form */
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
     * @return TIG_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_Form
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_Form();
        }

        return $this->_instance;
    }

    public function testGetOrderAs()
    {
        $paymentmethodMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod')
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
        $paymentmethodMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod')
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
        $paymentmethodMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod')
            ->getMock();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('getData')->with('_BPE_CompanyName')->willReturn('TIG');

        $instance = $this->_getInstance();
        $instance->setMethod($paymentmethodMock);
        $result = $instance->getCompanyName();
        $this->assertEquals('TIG', $result);
    }
}
