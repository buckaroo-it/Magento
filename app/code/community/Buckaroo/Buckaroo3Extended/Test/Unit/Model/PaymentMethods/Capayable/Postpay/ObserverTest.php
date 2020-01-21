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
class Buckaroo_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Capayable_Postpay_ObserverTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Capayable_Postpay_Observer */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions('checkout');
    }

    /**
     * @return null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Capayable_Postpay_Observer
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Capayable_Postpay_Observer();
        }

        return $this->_instance;
    }

    public function testGetCode()
    {
        $instance = $this->_getInstance();
        $result = $instance->getCode();

        $this->assertEquals('buckaroo3extended_capayablepostpay', $result);
    }

    public function testBuckaroo3extended_request_addservices()
    {
        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethod'))
            ->getMock();
        $mockPayment->expects($this->any())->method('getMethod')->willReturn('buckaroo3extended_capayablepostpay');

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getPayment', 'getPaymentMethodUsedForTransaction'))
            ->getMock();
        $mockOrder->expects($this->any())->method('getPayment')->will($this->returnValue($mockPayment));
        $mockOrder->expects($this->any())->method('getPaymentMethodUsedForTransaction')->willReturn(false);

        $mockRequest = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(null)
            ->getMock();

        $mockObserver = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(array('getOrder', 'getRequest'))
            ->getMock();
        $mockObserver->expects($this->any())->method('getOrder')->willReturn($mockOrder);
        $mockObserver->expects($this->any())->method('getRequest')->willReturn($mockRequest);

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_addservices($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Capayable_Postpay_Observer', $result);
        $this->assertEquals('Pay', $requestVarsResult['services']['Capayable']['action']);
        $this->assertEquals(1, $requestVarsResult['services']['Capayable']['version']);
    }
}
