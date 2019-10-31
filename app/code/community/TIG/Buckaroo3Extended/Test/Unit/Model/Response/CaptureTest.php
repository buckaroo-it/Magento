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
class TIG_Buckaroo3Extended_Test_Unit_Model_Response_CaptureTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_Response_Capture */
    protected $_instance = null;

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $params = array(
                'payment' => $this->_getMockPayment(),
                'debugEmail' => '',
                'response' => true,
                'XML' => false
            );

            $this->_instance = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Response_Capture')
                ->setMethods(array('_verifyResponse', '_parseResponse', '_addSubCodeComment', '_requiredAction'))
                ->setConstructorArgs(array($params))
                ->getMock();
        }

        return $this->_instance;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getMockPayment()
    {
        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getPayment'))
            ->getMock();

        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getOrder', 'getMethod'))
            ->getMock();
        $mockPayment->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($mockOrder));
        $mockPayment->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue('buckaroo3extended_afterpay'));

        $mockOrder->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($mockPayment));

        return $mockPayment;
    }

    public function testGetPayment()
    {
        $payment = $this->_getMockPayment();

        $instance = $this->_getInstance();
        $result = $instance->getPayment();

        $this->assertEquals($payment, $result);
    }

    public function testProcessResponse()
    {
        $instance = $this->_getInstance();
        $instance->setOrder($this->_getMockPayment()->getOrder());

        $instance->expects($this->once())->method('_verifyResponse')->willReturn(true);
        $instance->expects($this->once())
            ->method('_parseResponse')
            ->willReturn(array('status' => TIG_Buckaroo3Extended_Model_Response_Abstract::BUCKAROO_SUCCESS));
        $instance->expects($this->once())->method('_addSubCodeComment');
        $instance->expects($this->once())->method('_requiredAction');

        $instance->processResponse();
    }
}
