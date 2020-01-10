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
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_P24_ObserverTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_P24_Observer */
    protected $_instance = null;

    /**
     * @return null|TIG_Buckaroo3Extended_Model_PaymentMethods_P24_Observer
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_P24_Observer();
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
        $mockPayment->expects($this->any())->method('getMethod')->willReturn('buckaroo3extended_p24');

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getPayment','getPaymentMethodUsedForTransaction'))->getMock();
        $mockOrder->expects($this->any())->method('getPayment')->will($this->returnValue($mockPayment));
        $mockOrder->expects($this->any())->method('getPaymentMethodUsedForTransaction')->willReturn(false);

        return $mockOrder;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Varien_Event_Observer
     */
    protected function getMockObserver()
    {
        $mockOrder = $this->getMockOrder();

        $billingInfo = array(
            'firstname' => 'TIG',
            'lastname' => 'Support',
            'city' => 'Amsterdam',
            'address' => 'Kabelweg 37',
            'zip' => '1014 BA',
            'email' => 'email@gmail.com',
            'telephone' => '0201122233',
            'countryCode' => 'NL'
        );

        $mockRequest = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(array('getOrder', 'getBillingInfo'))
            ->getMock();
        $mockRequest->expects($this->any())->method('getOrder')->willReturn($mockOrder);
        $mockRequest->expects($this->any())->method('getBillingInfo')->willReturn($billingInfo);

        $mockObserver = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(array('getRequest', 'getOrder'))
            ->getMock();
        $mockObserver->expects($this->any())->method('getOrder')->willReturn($mockOrder);
        $mockObserver->expects($this->any())->method('getRequest')->willReturn($mockRequest);

        return $mockObserver;
    }

    public function testBuckaroo3extended_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_addservices($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_P24_Observer', $result);
        $this->assertEquals('Pay', $requestVarsResult['services']['Przelewy24']['action']);
    }

    public function testBuckaroo3extended_request_addcustomvars()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_addcustomvars($mockObserver);

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_P24_Observer', $result);

        $expectedBillingInfo = $mockObserver->getRequest()->getBillingInfo();

        $expected = array(
            'customVars' => array(
                'Przelewy24' => array(
                    'CustomerEmail'     => $expectedBillingInfo['email'],
                    'CustomerFirstName' => $expectedBillingInfo['firstname'],
                    'CustomerLastName'  => $expectedBillingInfo['lastname']
                )
            )
        );

        $requestVarsResult = $mockObserver->getRequest()->getVars();
        $this->assertEquals($expected, $requestVarsResult);
    }

    public function testBuckaroo3extended_request_setmethod()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_setmethod($mockObserver);
        $requestMethodResult = $mockObserver->getRequest()->getMethod();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_P24_Observer', $result);
        $this->assertEquals('p24', $requestMethodResult);
    }

    public function testBuckaroo3extended_refund_request_setmethod()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_refund_request_setmethod($mockObserver);
        $requestMethodResult = $mockObserver->getRequest()->getMethod();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_P24_Observer', $result);
        $this->assertEquals('p24', $requestMethodResult);
    }

    public function testBuckaroo3extended_refund_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_refund_request_addservices($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_P24_Observer', $result);
        $this->assertEquals('Refund', $requestVarsResult['services']['Przelewy24']['action']);
    }

    public function testBuckaroo3extended_refund_request_addcustomvars()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_refund_request_addcustomvars($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_P24_Observer', $result);

        $expected = array('channel' => 'Web');

        $this->assertEquals($expected, $requestVarsResult);
    }
}
