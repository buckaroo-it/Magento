<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
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
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Afterpay_ObserverTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay_Observer */
    protected $_instance = null;

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay_Observer();
        }

        return $this->_instance;
    }

    protected function _getMockOrder()
    {
        $mockOrderAddress = $this->getMockBuilder('Mage_Sales_Model_Order_Address')
            ->setMethods(null)
            ->getMock();

        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethod'))
            ->getMock();
        $mockPayment->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue('buckaroo3extended_afterpay'));

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(
                array('getPayment', 'getPaymentMethodUsedForTransaction', 'getBillingAddress', 'getShippingAddress')
            )
            ->getMock();
        $mockOrder->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($mockPayment));
        $mockOrder->expects($this->any())
            ->method('getPaymentMethodUsedForTransaction')
            ->will($this->returnValue(false));
        $mockOrder->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($mockOrderAddress));
        $mockOrder->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($mockOrderAddress));

        return $mockOrder;
    }

    /**
     * @return array
     */
    public function testBuckaroo3extended_request_addservicesDataprovider()
    {
        return array(
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_ORDER,
                'Pay'
            ),
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'Authorize'
            )
        );
    }

    /**
     * @param $paymethod
     * @param $expected
     *
     * @dataProvider testBuckaroo3extended_request_addservicesDataprovider
     */
    public function testBuckaroo3extended_request_addservices($paymethod, $expected)
    {
        $this->registerMockSessions(array('checkout'));

        $mockOrder = $this->_getMockOrder();

        $mockRequest = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_Abstract')
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
        Mage::app()->getStore()->setConfig('buckaroo/' . $instance->getCode() . '/payment_action', $paymethod);

        $resultInstance = $instance->buckaroo3extended_request_addservices($mockObserver);
        $resultVars = $mockRequest->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay_Observer', $resultInstance);

        $expectedVars = array(
            'services' => array(
                $instance->getMethod() => array(
                    'action' => $expected,
                    'version' => '1'
                )
            )
        );

        $this->assertEquals($expectedVars, $resultVars);
    }

    public function testBuckaroo3extended_request_addcustomvars()
    {
        $this->registerMockSessions(array('checkout'));

        $mockOrder = $this->_getMockOrder();

        $mockRequest = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(array('getOrder'))
            ->getMock();
        $mockRequest->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($mockOrder));

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
        $resultInstance = $instance->buckaroo3extended_request_addcustomvars($mockObserver);
        $resultVars = $mockRequest->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay_Observer', $resultInstance);

        $this->assertArrayHasKey('customVars', $resultVars);
        $this->assertArrayHasKey('Articles', $resultVars['customVars'][0]);
    }

    public function testBuckaroo3extended_capture_request_addservices()
    {
        $this->registerMockSessions(array('checkout'));

        $mockOrder = $this->_getMockOrder();

        $mockRequest = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_Abstract')
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
        $resultInstance = $instance->buckaroo3extended_capture_request_addservices($mockObserver);
        $resultVars = $mockRequest->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay_Observer', $resultInstance);

        $expectedVars = array(
            'services' => array(
                $instance->getMethod() => array(
                    'action' => 'Capture',
                    'version' => '1'
                )
            )
        );

        $this->assertEquals($expectedVars, $resultVars);
    }

    public function testBuckaroo3extended_capture_request_addcustomvars()
    {
        $this->registerMockSessions(array('checkout'));

        $mockOrder = $this->_getMockOrder();

        $mockRequest = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_Abstract')
            ->setMethods(array('getOrder'))
            ->getMock();
        $mockRequest->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue($mockOrder));

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
        $resultInstance = $instance->buckaroo3extended_capture_request_addcustomvars($mockObserver);
        $resultVars = $mockRequest->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay_Observer', $resultInstance);

        $this->assertArrayHasKey('customVars', $resultVars);
        $this->assertArrayHasKey('Articles', $resultVars['customVars'][0]);
    }

    public function testBuckaroo3extended_cancelauthorize_request_addservices()
    {
        $this->registerMockSessions(array('checkout'));

        $mockOrder = $this->_getMockOrder();

        $mockRequest = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_Abstract')
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
        $resultInstance = $instance->buckaroo3extended_cancelauthorize_request_addservices($mockObserver);
        $resultVars = $mockRequest->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay_Observer', $resultInstance);

        $expectedVars = array(
            'services' => array(
                $instance->getMethod() => array(
                    'action' => 'CancelAuthorize',
                    'version' => '1'
                )
            )
        );

        $this->assertEquals($expectedVars, $resultVars);
    }
}
