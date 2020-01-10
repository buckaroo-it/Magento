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
class TIG_Buckaroo3Extended_Test_Unit_Model_Observer_CancelAuthorizeTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_Observer_CancelAuthorize */
    protected $_instance = null;

    /** @var TIG_Buckaroo3Extended_Model_Request_CancelAuthorize */
    protected $_mockCancelAuthorizeRequest;

    public function setUp()
    {
        $this->registerMockSessions('checkout');

        $this->_mockCancelAuthorizeRequest = $this
            ->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_CancelAuthorize')
            ->setMethods(array('sendRequest'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->setModelMock('buckaroo3extended/request_cancelAuthorize', $this->_mockCancelAuthorizeRequest);
    }

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_Observer_CancelAuthorize();
        }

        return $this->_instance;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getMockPayment($paymentAction, $paymentCode)
    {
        $mockPaymentAbstract = $this->getMockBuilder('Mage_Payment_Model_Method_Abstract')
            ->setMethods(array('getConfigPaymentAction', 'getCode'))
            ->getMock();
        $mockPaymentAbstract->expects($this->any())
            ->method('getConfigPaymentAction')
            ->willReturn($paymentAction);
        $mockPaymentAbstract->expects($this->any())
            ->method('getCode')
            ->willReturn($paymentCode);

        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethodInstance'))
            ->getMock();
        $mockPayment->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($mockPaymentAbstract);

        return $mockPayment;
    }

    /**
     * @return array
     */
    public function testSales_order_payment_cancel_authorizeProvider()
    {
        return array(
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'buckaroo3extended_afterpay',
                'once'
            ),
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'buckaroo3extended_afterpay2',
                'once'
            ),
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_ORDER,
                'buckaroo3extended_afterpay',
                'never'
            ),
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'buckaroo3extended_klarna',
                'once'
            ),
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'buckaroo3extended_notAllowedMethod',
                'never'
            )
        );
    }

    /**
     * @param $paymentAction
     * @param $paymentMethod
     * @param $sendRequestExpects
     *
     * @dataProvider testSales_order_payment_cancel_authorizeProvider
     */
    public function testSales_order_payment_cancel_authorize($paymentAction, $paymentMethod, $sendRequestExpects)
    {
        // @codingStandardsIgnoreLine
        $_SERVER['PATH_INFO'] = 'sales_order/cancel';

        $mockPayment = $this->_getMockPayment($paymentAction, $paymentMethod);

        $mockObserver = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(array('getPayment'))
            ->getMock();
        $mockObserver->expects($this->once())
            ->method('getPayment')
            ->willReturn($mockPayment);

        $this->_mockCancelAuthorizeRequest
            ->expects($this->$sendRequestExpects())
            ->method('sendRequest');

        $instance = $this->_getInstance();
        $result = $instance->sales_order_payment_cancel_authorize($mockObserver);

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_Observer_CancelAuthorize', $result);
    }
}
