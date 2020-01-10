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
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Capayable_ObserverTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Observer */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions('checkout');
    }

    /**
     * @return null|TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Observer
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Observer();
            $this->setProperty('_code', 'buckaroo3extended_capayable', $this->_instance);
        }

        return $this->_instance;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Mage_Sales_Model_Order
     */
    protected function getMockOrder()
    {
        $mockOrderAddress = $this->getMockBuilder('Mage_Sales_Model_Order_Address')
            ->setMethods(array('getCountryId'))
            ->getMock();
        $mockOrderAddress->expects($this->any())->method('getCountryId')->willReturn('NL');

        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethod'))
            ->getMock();
        $mockPayment->expects($this->any())->method('getMethod')->willReturn('buckaroo3extended_capayable');

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(
                array('getPayment', 'getPaymentMethodUsedForTransaction', 'getBillingAddress', 'getShippingAddress')
            )
            ->getMock();
        $mockOrder->expects($this->any())->method('getPayment')->will($this->returnValue($mockPayment));
        $mockOrder->expects($this->any())->method('getPaymentMethodUsedForTransaction')->willReturn(false);
        $mockOrder->expects($this->any())->method('getBillingAddress')->willReturn($mockOrderAddress);
        $mockOrder->expects($this->any())->method('getShippingAddress')->willReturn($mockOrderAddress);

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
            ->setMethods(array('getOrder', 'getRequest'))
            ->getMock();
        $mockObserver->expects($this->any())->method('getOrder')->willReturn($mockOrder);
        $mockObserver->expects($this->any())->method('getRequest')->willReturn($mockRequest);

        return $mockObserver;
    }

    public function testBuckaroo3extended_request_setmethod()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_setmethod($mockObserver);
        $requestMethodResult = $mockObserver->getRequest()->getMethod();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Observer', $result);
        $this->assertEquals('capayable', $requestMethodResult);
    }

    public function testBuckaroo3extended_refund_request_addservices()
    {
        $mockObserver = $this->getMockObserver();
        $instance = $this->_getInstance();

        $expectedRequestVars = array(
            'services' => array(
                'Capayable' => array(
                    'action' => 'Refund',
                    'version' => 1
                )
            )
        );

        $result = $instance->buckaroo3extended_refund_request_addservices($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Observer', $result);
        $this->assertEquals($expectedRequestVars, $requestVarsResult);
    }

    /**
     * @return array
     */
    public function buckaroo3extended_refund_request_addcustomvarsProvider()
    {
        return array(
            'no channel parameter' => array(
                array('payment_method' => 'Capayable')
            ),
            'null parameter' => array(
                array('channel' => null)
            ),
            'empty string parameter' => array(
                array('channel' => '')
            ),
            'was callcenter' => array(
                array('channel' => 'CallCenter')
            ),
            'was web' => array(
                array('channel' => 'Web')
            ),
        );
    }

    /**
     * @param $channel
     *
     * @dataProvider buckaroo3extended_refund_request_addcustomvarsProvider
     */
    public function testBuckaroo3extended_refund_request_addcustomvars($channel)
    {
        $mockObserver = $this->getMockObserver();
        $mockObserver->getRequest()->setVars($channel);

        $instance = $this->_getInstance();

        $result = $instance->buckaroo3extended_refund_request_addcustomvars($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Observer', $result);
        $this->assertEquals('Web', $requestVarsResult['channel']);
    }

    public function testBuckaroo3extended_request_addcustomvars()
    {
        $mockObserver = $this->getMockObserver();
        $instance = $this->_getInstance();

        $result = $instance->buckaroo3extended_request_addcustomvars($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Observer', $result);
        $this->assertArrayHasKey('customVars', $requestVarsResult);
        $this->assertArrayHasKey('Articles', $requestVarsResult['customVars']['Capayable']);
    }

    public function testGetCompanyGroupData()
    {
        $sessionArray = array(
            'BPE_OrderAs' => 2,
            'BPE_CompanyCOCRegistration' => '123456789',
            'BPE_CompanyName' => 'TIG',
        );

        $expectedResult = array(
            'Name' => array(
                'value' => 'TIG',
                'group' => 'Company'
            ),
            'ChamberOfCommerce' => array(
                'value' => '123456789',
                'group' => 'Company'
            )
        );

        $checkoutSession = Mage::getSingleton('core/session');
        $checkoutSession->method('getData')->with('additionalFields')->willReturn($sessionArray);

        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'getCompanyGroupData');
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getCustomerTypeProvider()
    {
        return array(
            'not set' => array(
                array('BPE_Company' => 'TIG'),
                ''
            ),
            'empty' => array(
                array('BPE_OrderAs' => ''),
                ''
            ),
            'incorrect type' => array(
                array('BPE_OrderAs' => '5'),
                ''
            ),
            'debtor' => array(
                array('BPE_OrderAs' => '1'),
                'Debtor'
            ),
            'company' => array(
                array('BPE_OrderAs' => '2'),
                'Company'
            ),
            'soleproprietor' => array(
                array('BPE_OrderAs' => '3'),
                'SoleProprietor'
            ),
        );
    }

    /**
     * @param $additionalFields
     * @param $expected
     *
     * @dataProvider getCustomerTypeProvider
     */
    public function testGetCustomerType($additionalFields, $expected)
    {
        $checkoutSession = Mage::getSingleton('core/session');
        $checkoutSession->method('getData')->with('additionalFields')->willReturn($additionalFields);

        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'getCustomerType');

        $this->assertEquals($expected, $result);
    }

    public function testGetProductArticle()
    {
        $groupId = 3;
        $articleValues = array(
            'Code' => 'tig-001',
            'Name' => 'TIG Product',
            'Quantity' => '2',
            'Price' => '4.78',
        );

        $productMock = $this->getMockBuilder('Mage_Sales_Model_Order_Item')
            ->setMethods(array('getSku', 'getName', 'getQtyOrdered', 'getBasePriceInclTax'))
            ->getMock();
        $productMock->expects($this->once())->method('getSku')->willReturn($articleValues['Code']);
        $productMock->expects($this->once())->method('getName')->willReturn($articleValues['Name']);
        $productMock->expects($this->once())->method('getQtyOrdered')->willReturn($articleValues['Quantity']);
        $productMock->expects($this->once())->method('getBasePriceInclTax')->willReturn($articleValues['Price']);

        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'getProductArticle', array($productMock, $groupId));

        $this->assertInternalType('array', $result);

        foreach ($result as $name => $groupValue) {
            $this->assertEquals($articleValues[$name], $groupValue['value']);
            $this->assertEquals('ProductLine', $groupValue['group']);
            $this->assertEquals($groupId, $groupValue['groupId']);
        }
    }

    public function testGetSubtotalLine()
    {
        $groupId = 2;
        $articleValues = array(
            'Name' => 'Shipping',
            'Value' => '5.98',
        );

        $params = array($articleValues['Name'], $articleValues['Value'], $groupId);

        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'getSubtotalLine', $params);

        $this->assertInternalType('array', $result);

        foreach ($result as $name => $groupValue) {
            $this->assertEquals($articleValues[$name], $groupValue['value']);
            $this->assertEquals('SubtotalLine', $groupValue['group']);
            $this->assertEquals($groupId, $groupValue['groupId']);
        }
    }
}
