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
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Afterpay2_PaymentMethodTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay2_PaymentMethod */
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

        $mockCaptureResponse = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Response_Capture')
            ->setConstructorArgs(array($params))
            ->getMock();

        $this->setModelMock('buckaroo3extended/response_capture', $mockCaptureResponse);

        // final classes are not mockable, so mock the superclass instead
        $mockSoap = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Abstract')
            ->setConstructorArgs(
                array(
                    'vars' => array(),
                    'method' => 'buckaroo3extended_afterpay2'
                )
            )
            ->getMock();

        $this->setModelMock('buckaroo3extended/soap', $mockSoap);
    }

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay2_PaymentMethod();
        }

        return $this->_instance;
    }

    /**
     * @return mixed
     */
    protected function _getMockPayment()
    {
        $mockOrderAddress = $this->getMockBuilder('Mage_Sales_Model_Order_Address')
            ->setMethods(array('getCountryId'))
            ->getMock();
        $mockOrderAddress->expects($this->any())
            ->method('getCountryId')
            ->will($this->returnValue('NL'));

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getPayment', 'getBillingAddress', 'getShippingAddress'))
            ->getMock();
        $mockOrder->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($mockOrderAddress));
        $mockOrder->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($mockOrderAddress));

        $mockPaymentInfo = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getOrder'))
            ->getMock();
        $mockPaymentInfo->expects($this->any())
            ->method('getOrder')
            ->willReturn($mockOrder);

        $mockOrder->expects($this->any())
            ->method('getPayment')
            ->willReturn($mockPaymentInfo);

        return $mockPaymentInfo;
    }

    public function testCanOrder()
    {
        $instance = $this->_getInstance();
        $result = $instance->canCapture();

        $this->assertTrue($result);
    }

    /**
     * @return array
     */
    public function canCaptureTestProvider()
    {
        return array(
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_ORDER,
                false
            ),
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                true
            ),
            array(
                Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE,
                true
            )
        );
    }

    /**
     * @param $paymentAction
     * @param $expected
     *
     * @dataProvider canCaptureTestProvider
     */
    public function testCanCapture($paymentAction, $expected)
    {
        $instance = $this->_getInstance();
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended_afterpay2/payment_action', $paymentAction);
        $result = $instance->canCapture();

        $this->assertEquals($expected, $result);
    }

    public function testCapture()
    {
        $mockPaymentInfo = $this->_getMockPayment();
        $instance = $this->_getInstance();

        $result = $instance->capture($mockPaymentInfo, 0);

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay2_PaymentMethod', $result);
    }

    /**
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage Capture action is not available.
     */
    public function testShouldThrowAnExceptionIfCantCapture()
    {
        $instance = $this->_getInstance();
        Mage::app()->getStore()->setConfig(
            'buckaroo/buckaroo3extended_afterpay2/payment_action',
            Mage_Payment_Model_Method_Abstract::ACTION_ORDER
        );
        $instance->capture(new Varien_Object(), 0);
    }

    public function testValidate()
    {
        $mockPaymentInfo = $this->_getMockPayment();

        $instance = $this->_getInstance();
        $instance->setData('info_instance', $mockPaymentInfo);

        $postData = array(
            $instance->getCode() . '_bpe_accept'                  => 'checked',
            $instance->getCode() . '_bpe_customer_account_number' => 'NL32INGB0000012345',
            $instance->getCode() . '_BPE_Customergender'          => 1,
            $instance->getCode() . '_bpe_customer_phone_number'   => '0513744112',
            $instance->getCode() . '_BPE_BusinessSelect'          => 1,
            'payment' => array(
                $instance->getCode() => array(
                    'year' => 1990,
                    'month' => 01,
                    'day' => 01
                )
            )
        );

        $request = Mage::app()->getRequest();
        $request->setPost($postData);

        $result = $instance->validate();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay2_PaymentMethod', $result);
    }

    /**
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage Please accept the terms and conditions.
     */
    public function testShouldThrowAnExceptionIfNotAcceptedTos()
    {
        $instance = $this->_getInstance();
        $instance->validate();
    }

    /**
     * @return array
     */
    public function getRejectedMessageProvider()
    {
        return array(
            'has rejected message' => array(
                (Object)array(
                    'ConsumerMessage' => (Object)array(
                        'HtmlText' => 'Error from Payment Plaza'
                    )
                ),
                'Error from Payment Plaza'
            ),
            'has no rejected message' => array(
                (Object)array(
                    'ConsumerMessage' => (Object)array(
                        'HtmlText' => ''
                    )
                ),
                false
            ),
            'has no HtmlText' => array(
                (Object)array(
                    'ConsumerMessage' => (Object)array(
                        'someOtherObject' => 'TIG response'
                    )
                ),
                false
            ),
            'has no ConsumerMessage' => array(
                (Object)array(
                    'transaction' => 'abcdef123456'
                ),
                false
            ),
        );
    }

    /**
     * @param $responseData
     * @param $expected
     *
     * @dataProvider getRejectedMessageProvider
     */
    public function testGetRejectedMessage($responseData, $expected)
    {
        $instance = $this->_getInstance();
        $result = $instance->getRejectedMessage($responseData);
        $this->assertEquals($expected, $result);
    }
}
