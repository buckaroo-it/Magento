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
class Buckaroo_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Afterpay20_ObserverTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_Observer */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions(array('checkout'));
    }

    /**
     * @return Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_Observer
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_Observer();
        }

        return $this->_instance;
    }

    protected function _getMockOrder()
    {
        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethod'))
            ->getMock();
        $mockPayment->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue('buckaroo3extended_afterpay20'));

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(
                array('getPayment', 'getPaymentMethodUsedForTransaction')
            )
            ->getMock();
        $mockOrder->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($mockPayment));
        $mockOrder->expects($this->any())
            ->method('getPaymentMethodUsedForTransaction')
            ->will($this->returnValue(false));

        return $mockOrder;
    }

    public function testBuckaroo3extended_request_addservices()
    {
        $mockOrder = $this->_getMockOrder();

        $mockRequest = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(null)
            ->getMock();

        $mockObserver = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(array('getOrder', 'getRequest'))
            ->getMock();
        $mockObserver->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($mockOrder));
        $mockObserver->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($mockRequest));


        $instance = $this->_getInstance();

        $resultInstance = $instance->buckaroo3extended_request_addservices($mockObserver);
        $resultVars = $mockRequest->getVars();

        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_Observer', $resultInstance);

        $expectedVars = array(
            'services' => array(
                $instance->getMethod() => array(
                    'action' => 'Pay',
                    'version' => '1'
                )
            )
        );

        $this->assertEquals($expectedVars, $resultVars);
    }

    public function testBuckaroo3extended_request_setmethod()
    {
        $mockOrder = $this->_getMockOrder();

        $mockRequest = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(null)
            ->getMock();

        $mockObserver = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(array('getOrder', 'getRequest'))
            ->getMock();
        $mockObserver->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($mockOrder));
        $mockObserver->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($mockRequest));


        $instance = $this->_getInstance();

        $resultInstance = $instance->buckaroo3extended_request_setmethod($mockObserver);
        $resultVars = $mockRequest->getMethod();

        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_Observer', $resultInstance);
        $this->assertEquals('afterpay20', $resultVars);
    }

    public function testBuckaroo3extended_refund_request_setmethod()
    {
        $mockOrder = $this->_getMockOrder();

        $mockRequest = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(null)
            ->getMock();

        $mockObserver = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(array('getOrder', 'getRequest'))
            ->getMock();
        $mockObserver->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($mockOrder));
        $mockObserver->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($mockRequest));


        $instance = $this->_getInstance();

        $resultInstance = $instance->buckaroo3extended_refund_request_setmethod($mockObserver);
        $resultVars = $mockRequest->getMethod();

        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_Observer', $resultInstance);
        $this->assertEquals('afterpay20', $resultVars);
    }

    public function testBuckaroo3extended_refund_request_addservices()
    {
        $mockOrder = $this->_getMockOrder();

        $mockRequest = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(null)
            ->getMock();

        $mockObserver = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(array('getOrder', 'getRequest'))
            ->getMock();
        $mockObserver->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($mockOrder));
        $mockObserver->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($mockRequest));


        $instance = $this->_getInstance();

        $resultInstance = $instance->buckaroo3extended_refund_request_addservices($mockObserver);
        $resultVars = $mockRequest->getVars();

        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_Observer', $resultInstance);

        $expectedVars = array(
            'services' => array(
                $instance->getMethod() => array(
                    'action' => 'Refund',
                    'version' => '1'
                )
            )
        );

        $this->assertEquals($expectedVars, $resultVars);
    }

    public function testGetHelper()
    {
        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'getHelper');
        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Helper_Data', $result);
    }

    public function getParameterLineProvider()
    {
        return array(
            'only name and value' => array(
                'Company',
                'Buckaroo',
                null,
                null,
                array(
                    'name' => 'Company',
                    'value' => 'Buckaroo'
                )
            ),
            'with group' => array(
                'Company',
                'Buckaroo',
                'Billing',
                null,
                array(
                    'name' => 'Company',
                    'value' => 'Buckaroo',
                    'group' => 'Billing'
                )
            ),
            'with groupid' => array(
                'Company',
                'Buckaroo',
                null,
                37,
                array(
                    'name' => 'Company',
                    'value' => 'Buckaroo',
                    'groupId' => 37
                )
            ),
            'with both group and groupid' => array(
                'Company',
                'Buckaroo',
                'Billing',
                37,
                array(
                    'name' => 'Company',
                    'value' => 'Buckaroo',
                    'group' => 'Billing',
                    'groupId' => 37
                )
            ),
        );
    }

    /**
     * @param $name
     * @param $value
     * @param $group
     * @param $groupId
     * @param $expected
     *
     * @dataProvider getParameterLineProvider
     */
    public function testGetParameterLine($name, $value, $group, $groupId, $expected)
    {
        $params = array($name, $value, $group, $groupId);
        $instance = $this->_getInstance();

        $result = $this->invokeMethod($instance, 'getParameterLine', $params);
        $this->assertEquals($expected, $result);
    }
}