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
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Klarna_ObserverTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions('checkout');
    }

    /**
     * @return null|TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer();
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
        $mockOrderAddress->expects($this->any())
            ->method('getCountryId')
            ->will($this->returnValue('NL'));

        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethod'))
            ->getMock();
        $mockPayment->expects($this->any())->method('getMethod')->willReturn('buckaroo3extended_klarna');

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

        $mockRequest = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(array('getOrder'))
            ->getMock();
        $mockRequest->expects($this->any())->method('getOrder')->willReturn($mockOrder);

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

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer', $result);
        $this->assertEquals('klarna', $requestMethodResult);
    }

    public function testBuckaroo3extended_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_addservices($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer', $result);
        $this->assertEquals('DataRequest', $requestVarsResult['request_type']);
        $this->assertEquals('Reserve', $requestVarsResult['services']['klarna']['action']);
    }

    public function testBuckaroo3extended_request_addcustomvars()
    {
        $this->registerMockSessions();
        $randOrdernumber = rand(1000, 9999);

        $mockObserver = $this->getMockObserver();
        $mockRequest = $mockObserver->getRequest();
        $mockRequest->setVars(array('orderId' => $randOrdernumber, 'invoiceId' => $randOrdernumber));

        $mockOrder = $this->getMockOrder();

        $instance = $this->_getInstance();
        $this->setProperty('_order', $mockOrder, $instance);
        $result = $instance->buckaroo3extended_request_addcustomvars($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer', $result);
        $this->assertArrayHasKey('customVars', $requestVarsResult);
        $this->assertArrayHasKey('Articles', $requestVarsResult['customVars']['klarna']);

        $this->assertEquals($randOrdernumber, $requestVarsResult['invoiceId']);
        $this->assertArrayNotHasKey('amountCredit', $requestVarsResult);
        $this->assertArrayNotHasKey('amountDebit', $requestVarsResult);
        $this->assertArrayNotHasKey('orderId', $requestVarsResult);
    }

    public function testBuckaroo3extended_refund_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_refund_request_addservices($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer', $result);
        $this->assertEquals('Refund', $requestVarsResult['services']['klarna']['action']);
    }

    public function testBuckaroo3extended_refund_request_addcustomvars()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_refund_request_addcustomvars($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer', $result);
        $this->assertEquals('Web', $requestVarsResult['channel']);
    }

    public function testBuckaroo3extended_capture_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_capture_request_addservices($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer', $result);
        $this->assertEquals('Pay', $requestVarsResult['services']['klarna']['action']);
    }

    public function testBuckaroo3extended_capture_request_addcustomvars()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_capture_request_addcustomvars($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer', $result);
        $this->assertArrayHasKey('customVars', $requestVarsResult);
        $this->assertArrayHasKey('Articles', $requestVarsResult['customVars']['klarna']);
        $this->assertArrayNotHasKey('OriginalTransactionKey', $requestVarsResult);
    }

    public function testBuckaroo3extended_cancelauthorize_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_cancelauthorize_request_addservices($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer', $result);
        $this->assertEquals('CancelReservation', $requestVarsResult['services']['klarna']['action']);
    }

    public function testBuckaroo3extended_cancelauthorize_request_addcustomvars()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_cancelauthorize_request_addcustomvars($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer', $result);

        $this->assertArrayHasKey('customVars', $requestVarsResult);
        $this->assertArrayHasKey('ReservationNumber', $requestVarsResult['customVars']['klarna']);

        $this->assertArrayNotHasKey('amountCredit', $requestVarsResult);
        $this->assertArrayNotHasKey('OriginalTransactionKey', $requestVarsResult);
    }

    /**
     * @return array
     */
    public function shippingSameAsBillingProvider()
    {
        return array(
            'Same Addresses' => array(
                array(
                    'street' => 'kabelweg',
                    'zipcode' => '1014BA'
                ),
                array(
                    'street' => 'kabelweg',
                    'zipcode' => '1014BA'
                ),
                'true'
            ),
            'Different Addresses' => array(
                array(
                    'street' => 'kabelweg',
                    'zipcode' => '1014BA'
                ),
                array(
                    'street' => 'abc street',
                    'zipcode' => '1234 DC'
                ),
                'false'
            )
        );
    }

    /**
     * @param $billingData
     * @param $shippingData
     * @param $expected
     *
     * @dataProvider shippingSameAsBillingProvider
     */
    public function testShippingSameAsBilling($billingData, $shippingData, $expected)
    {
        $mockBillingAddress = $this->getMockBuilder('Mage_Sales_Model_Order_Address')
            ->setMethods(array('getData'))
            ->getMock();
        $mockBillingAddress->expects($this->any())->method('getData')->willReturn($billingData);

        $mockShippingAddress = $this->getMockBuilder('Mage_Sales_Model_Order_Address')
            ->setMethods(array('getData'))
            ->getMock();
        $mockShippingAddress->expects($this->any())->method('getData')->willReturn($shippingData);

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getBillingAddress', 'getShippingAddress'))
            ->getMock();
        $mockOrder->expects($this->any())->method('getBillingAddress')->willReturn($mockBillingAddress);
        $mockOrder->expects($this->any())->method('getShippingAddress')->willReturn($mockShippingAddress);

        $instance = $this->_getInstance();
        $this->setProperty('_order', $mockOrder, $instance);
        $result = $this->invokeMethod($instance, 'shippingSameAsBilling');

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function processAddressProvider()
    {
        return array(
            'only street' => array(
                'Kabelweg',
                array(
                    'street' => 'Kabelweg',
                    'house_number' => '',
                    'number_addition' => ''
                )
            ),
            'with housenumber' => array(
                'Kabelweg 37',
                array(
                    'street' => 'Kabelweg',
                    'house_number' => '37',
                    'number_addition' => ''
                )
            ),
            'with number addition' => array(
                'Kabelweg 37 A',
                array(
                    'street' => 'Kabelweg',
                    'house_number' => '37',
                    'number_addition' => 'A'
                )
            ),
        );
    }

    /**
     * @param $fullStreet
     * @param $expected
     *
     * @dataProvider processAddressProvider
     */
    public function testProcessAddress($fullStreet, $expected)
    {
        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'processAddress', array($fullStreet));

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function processPhoneNumberProvider()
    {
        return array(
            'phone 13 length' => array(
                '0031201234567',
                '0031201234567',
                false
            ),
            'mobile 13 length' => array(
                '0031612345678',
                '0031612345678',
                true
            ),
            'phone 12 length' => array(
                '+31201234567',
                '0031201234567',
                false
            ),
            'mobile 12 length' => array(
                '+31612345678',
                '0031612345678',
                true
            ),
            'phone 11 length' => array(
                '31201234567',
                '0031201234567',
                false
            ),
            'mobile 11 length' => array(
                '31612345678',
                '0031612345678',
                true
            ),
            'phone 10 length' => array(
                '0201234567',
                '0031201234567',
                false
            ),
            'mobile 10 length' => array(
                '0612345678',
                '0031612345678',
                true
            ),
        );
    }

    /**
     * @param $telephoneNumber
     * @param $expectedClean
     * @param $expectedMobile
     *
     * @dataProvider processPhoneNumberProvider
     */
    public function testProcessPhoneNumber($telephoneNumber, $expectedClean, $expectedMobile)
    {
        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'processPhoneNumber', array($telephoneNumber));

        $this->assertEquals($expectedClean, $result['clean']);
        $this->assertEquals($expectedMobile, $result['mobile']);
    }

    /**
     * @return array
     */
    public function processPhoneNumberBeProvider()
    {
        return array(
            'phone 13 length' => array(
                '003212345678',
                '003212345678',
                false
            ),
            'mobile 13 length' => array(
                '0032461234567',
                '0032461234567',
                true
            ),
            'phone 12 length' => array(
                '+3212345678',
                '003212345678',
                false
            ),
            'mobile 12 length' => array(
                '+32461234567',
                '0032461234567',
                true
            ),
            'phone 11 length' => array(
                '3212345678',
                '003212345678',
                false
            ),
            'mobile 11 length' => array(
                '32461234567',
                '0032461234567',
                true
            ),
            'phone 10 length' => array(
                '012345678',
                '003212345678',
                false
            ),
            'mobile 10 length' => array(
                '0461234567',
                '0032461234567',
                true
            ),
        );
    }

    /**
     * @param $telephoneNumber
     * @param $expectedClean
     * @param $expectedMobile
     *
     * @dataProvider processPhoneNumberBeProvider
     */
    public function testProcessPhoneNumberBe($telephoneNumber, $expectedClean, $expectedMobile)
    {
        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'processPhoneNumberBe', array($telephoneNumber));

        $this->assertEquals($expectedClean, $result['clean']);
        $this->assertEquals($expectedMobile, $result['mobile']);
    }

    /**
     * @return array
     */
    public function getPaymentFeeLineProvider()
    {
        $articleType = TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer::KLARNA_ARTICLE_TYPE_HANDLINGFEE;

        return array(
            'excl fee' => array(
                0,
                false
            ),
            'incl fee' => array(
                2,
                array(
                    'ArticleNumber' => array('value' => 1),
                    'ArticlePrice' => array('value' => 2),
                    'ArticleQuantity' => array('value' => 1),
                    'ArticleTitle' => array('value' => 'Servicekosten'),
                    'ArticleVat' => array('value' => 0.00),
                    'ArticleType' => array('value' => $articleType),
                )
            )
        );
    }

    /**
     * @param $fee
     * @param $expected
     *
     * @dataProvider getPaymentFeeLineProvider
     */
    public function testGetPaymentFeeLine($fee, $expected)
    {
        $taxCalcMock = $this->getMockBuilder('Mage_Tax_Model_Calculation')->setMethods(array('getRate'))->getMock();
        $taxCalcMock->expects($this->once())->method('getRate')->willReturn(0.00);
        $this->setModelMock('tax/calculation', $taxCalcMock);

        $mockOrder = $this->getMockOrder();
        $mockOrder->setBuckarooFee($fee);

        $instance = $this->_getInstance();
        $this->setProperty('_order', $mockOrder, $instance);
        $result = $this->invokeMethod($instance, 'getPaymentFeeLine');

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getShipmentCostsLineProvider()
    {
        $articleType = TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_Observer::KLARNA_ARTICLE_TYPE_SHIPMENTFEE;

        return array(
            'excl shipment costs' => array(
                0,
                false
            ),
            'incl shipment costs' => array(
                1.50,
                array(
                    'ArticleNumber' => array('value' => 2),
                    'ArticlePrice' => array('value' => 1.50),
                    'ArticleQuantity' => array('value' => 1),
                    'ArticleTitle' => array('value' => 'Verzendkosten'),
                    'ArticleVat' => array('value' => 0.00),
                    'ArticleType' => array('value' => $articleType),
                )
            )
        );
    }

    /**
     * @param $shipmentCosts
     * @param $expected
     *
     * @dataProvider getShipmentCostsLineProvider
     */
    public function testGetShipmentCostsLine($shipmentCosts, $expected)
    {
        $taxCalcMock = $this->getMockBuilder('Mage_Tax_Model_Calculation')->setMethods(array('getRate'))->getMock();
        $taxCalcMock->expects($this->once())->method('getRate')->willReturn(0.00);
        $this->setModelMock('tax/calculation', $taxCalcMock);

        $mockOrder = $this->getMockOrder();
        $mockOrder->setBaseShippingInclTax($shipmentCosts);

        $instance = $this->_getInstance();
        $this->setProperty('_order', $mockOrder, $instance);
        $result = $this->invokeMethod($instance, 'getShipmentCostsLine');

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getReservationNumberProvider()
    {
        return array(
            'no parameters' => array(
                (Object)array(),
                null
            ),
            'single parameter' => array(
                (Object)array(
                    'Name' => 'ReservationNumber',
                    '_' => '12345'
                ),
                '12345'
            ),
            'single parameter without reservation number' => array(
                (Object)array(
                    'Name' => 'orderId',
                    '_' => '54321'
                ),
                null
            ),
            'multiple parameters' => array(
                (Object)array(
                    (Object)array(
                        'Name' => 'transactionId',
                        '_' => 'abc321def654'
                    ),
                    (Object)array(
                        'Name' => 'ReservationNumber',
                        '_' => '67890'
                    )
                ),
                '67890'
            ),
            'multiple parameters without reservation number' => array(
                (Object)array(
                    (Object)array(
                        'Name' => 'transactionId',
                        '_' => 'abc321def654'
                    ),
                    (Object)array(
                        'Name' => 'orderId',
                        '_' => '54321'
                    )
                ),
                null
            )
        );
    }

    /**
     * @param $parameters
     * @param $expected
     *
     * @dataProvider getReservationNumberProvider
     */
    public function testGetReservationNumber($parameters, $expected)
    {
        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'getReservationNumber', array($parameters));

        $this->assertEquals($expected, $result);
    }
}
