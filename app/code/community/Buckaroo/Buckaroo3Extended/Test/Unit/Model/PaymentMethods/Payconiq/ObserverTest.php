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
class Buckaroo_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Payconiq_ObserverTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions('checkout');
    }

    /**
     * @return null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer();
        }

        return $this->_instance;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Mage_Sales_Model_Order
     */
    protected function getMockOrder()
    {
        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethod'))
            ->getMock();
        $mockPayment->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue('buckaroo3extended_payconiq'));

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getPayment', 'getPaymentMethodUsedForTransaction', 'getTransactionKey'))
            ->getMock();
        $mockOrder->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($mockPayment));
        $mockOrder->expects($this->any())
            ->method('getPaymentMethodUsedForTransaction')
            ->will($this->returnValue(false));

        return $mockOrder;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Varien_Event_Observer
     */
    protected function getMockObserver()
    {
        $orderMock = $this->getMockOrder();

        $mockRequest = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(null)
            ->getMock();

        $mockObserver = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(array('getOrder', 'getRequest'))
            ->getMock();
        $mockObserver->method('getOrder')->will($this->returnValue($orderMock));
        $mockObserver->method('getRequest')->will($this->returnValue($mockRequest));

        return $mockObserver;
    }

    public function testBuckaroo3extended_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_addservices($mockObserver);
        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer', $result);

        $resultVars = $mockObserver->getRequest()->getVars();
        $expectedVars = $array = array(
            'services' => array(
                'payconiq' => array(
                    'action'  => 'Pay',
                    'version' => 1
                )
            )
        );

        $this->assertEquals($expectedVars, $resultVars);
    }

    public function testBuckaroo3extended_request_addcustomvars()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_addcustomvars($mockObserver);
        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer', $result);
    }

    public function testBuckaroo3extended_request_setmethod()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_setmethod($mockObserver);

        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer', $result);
        $this->assertEquals('payconiq', $mockObserver->getRequest()->getMethod());
    }

    public function testBuckaroo3extended_cancelauthorize_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $transactionKey = 'key123abc';
        $orderMock = $mockObserver->getOrder();
        $orderMock->expects($this->once())->method('getTransactionKey')->willReturn($transactionKey);

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_cancelauthorize_request_addservices($mockObserver);
        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer', $result);

        $expectedVars = array(
            'request_type' => 'CancelTransaction',
            'TransactionKey' => $transactionKey
        );

        $this->assertEquals($expectedVars, $mockObserver->getRequest()->getVars());
    }

    public function testBuckaroo3extended_refund_request_setmethod()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_refund_request_setmethod($mockObserver);

        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer', $result);
        $this->assertEquals('payconiq', $mockObserver->getRequest()->getMethod());
    }

    public function testBuckaroo3extended_refund_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_refund_request_addservices($mockObserver);
        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer', $result);

        $expectedVars = array(
            'services' => array(
                'payconiq' => array(
                    'action'  => 'Refund',
                    'version' => 1
                )
            )
        );

        $this->assertEquals($expectedVars, $mockObserver->getRequest()->getVars());
    }

    public function testBuckaroo3extended_refund_request_addcustomvars()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_refund_request_addcustomvars($mockObserver);
        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Payconiq_Observer', $result);
    }
}
