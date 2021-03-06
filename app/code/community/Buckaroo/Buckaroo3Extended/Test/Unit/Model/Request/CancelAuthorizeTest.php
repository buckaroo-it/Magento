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
class Buckaroo_Buckaroo3Extended_Test_Unit_Model_Request_CancelAuthorizeTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Model_Request_CancelAuthorize */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions();
        Mage::app()->getStore()->setCurrentCurrencyCode('EUR');

        $params = array(
            'payment' => $this->_getMockPayment(),
            'debugEmail' => '',
            'response' => false,
            'XML' => false
        );

        $mockCancelAuthorizeResponse = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_Response_CancelAuthorize')
            ->setConstructorArgs(array($params))
            ->getMock();

        $this->setModelMock('buckaroo3extended/response_cancelAuthorize', $mockCancelAuthorizeResponse);

        // final classes are not mockable, so mock the superclass instead
        $mockSoap = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_Abstract')
            ->setConstructorArgs(
                array(
                    'vars' => array(),
                    'method' => 'buckaroo3extended_afterpay'
                )
            )
            ->getMock();

        $this->setModelMock('buckaroo3extended/soap', $mockSoap);
    }

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $params = array('payment' => $this->_getMockPayment());

            $this->_instance = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_Request_CancelAuthorize')
                ->setMethods(
                    array(
                        '_addBaseVariables',
                        '_addOrderVariables',
                        '_addShopVariables',
                        '_addSoftwareVariables',
                        '_addCancelAuthorizeVariables'
                    )
                )
                ->setConstructorArgs(array($params))
                ->getMock();
        }

        return $this->_instance;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getMockOrderAddress()
    {
        $mockOrderAddress = $this->getMockBuilder('Mage_Sales_Model_Order_Address')
            ->setMethods(array('getData', 'getStreetFull', 'getFirstname'))
            ->getMock();
        $mockOrderAddress->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(array()));
        $mockOrderAddress->expects($this->any())
            ->method('getStreetFull')
            ->will($this->returnValue('Hoofdstraat 90 1'));
        $mockOrderAddress->expects($this->any())
            ->method('getFirstname')
            ->will($this->returnValue('Test'));

        return $mockOrderAddress;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getMockPayment()
    {
        $mockOrderAddress = $this->_getMockOrderAddress();

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getBillingAddress', 'getShippingAddress', 'getPayment'))
            ->getMock();
        $mockOrder->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($mockOrderAddress));
        $mockOrder->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($mockOrderAddress));

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

    public function testSendRequest()
    {
        $instance = $this->_getInstance();

        $instance->expects($this->once())->method('_addBaseVariables');
        $instance->expects($this->once())->method('_addOrderVariables');
        $instance->expects($this->once())->method('_addShopVariables');
        $instance->expects($this->once())->method('_addSoftwareVariables');
        $instance->expects($this->once())->method('_addCancelAuthorizeVariables');

        $instance->sendRequest();
    }
}
