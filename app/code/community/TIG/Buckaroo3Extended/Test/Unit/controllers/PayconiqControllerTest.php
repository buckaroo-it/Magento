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
class TIG_Buckaroo3Extended_Test_Unit_PayconiqControllerTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_PayconiqController */
    protected $_instance = null;

    public static function setUpBeforeClass()
    {
        // Controller classes aren't loaded through autoload, so they need to be loaded manually
        // @codingStandardsIgnoreLine
        require_once(__DIR__ . '/../../../controllers/PayconiqController.php');
    }

    /**
     * @return TIG_Buckaroo3Extended_PayconiqController
     */
    protected function _getInstance()
    {
        if ($this->_instance !== null) {
            return $this->_instance;
        }

        $this->prepareFrontendDispatch();
        $request = Mage::app()->getRequest();
        $response = Mage::app()->getResponse();
        $this->_instance = new TIG_Buckaroo3Extended_PayconiqController($request, $response);

        return $this->_instance;
    }

    public function testCheckoutAction()
    {
        $requestBuilderMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_Abstract')
            ->disableOriginalConstructor()
            ->setMethods(array('setResponseModelClass', 'sendRequest'))
            ->getMock();
        $requestBuilderMock->expects($this->once())
            ->method('setResponseModelClass')
            ->with('buckaroo3extended/response_payconiq');
        $requestBuilderMock->expects($this->once())->method('sendRequest');

        $this->setModelMock('buckaroo3extended/request_abstract', $requestBuilderMock);

        $instance = $this->_getInstance();
        $instance->checkoutAction();
    }

    public function testPayAction()
    {
        $instance = $this->_getInstance();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('__call')
            ->withConsecutive(array('getLastSuccessQuoteId'), array('getLastRealOrderId'))
            ->willReturnOnConsecutiveCalls(12, 34);

        Mage::app()->getFrontController()->setNoRender(true);

        $instance->payAction();
    }

    public function testCancelAction()
    {
        $instance = $this->_getInstance();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('__call')
            ->withConsecutive(array('getLastSuccessQuoteId'), array('getLastRealOrderId'))
            ->willReturnOnConsecutiveCalls(12, 34);

        $existingOrderMock = $this->getMockBuilder('Mage_Sales_Model_Order')->disableOriginalConstructor()->getMock();
        $existingOrderMock->method('cancel')->willReturnSelf();

        $paymentMock = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('setAdditionalInformation','save'))
            ->getMock();
        $paymentMock->expects($this->once())->method('setAdditionalInformation');
        $paymentMock->expects($this->once())->method('save');

        $existingOrderMock->method('getPayment')
            ->will($this->returnValue($paymentMock));

        $this->setProperty('_order', $existingOrderMock, $instance);

        $cancelAuthorizeMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_CancelAuthorize')
            ->disableOriginalConstructor()
            ->getMock();
        $this->setModelMock('buckaroo3extended/request_cancelAuthorize', $cancelAuthorizeMock);

        $instance->cancelAction();
    }

    /**
     * @return array
     */
    public function canShowPageProvider()
    {
        return array(
            'no ids' => array(
                null,
                null,
                false
            ),
            'quote id' => array(
                1234,
                null,
                false
            ),
            'order id' => array(
                null,
                5678,
                false
            ),
            'both ids' => array(
                9012,
                3456,
                true
            ),
        );
    }

    /**
     * @param $quoteId
     * @param $orderId
     * @param $expected
     *
     * @dataProvider canShowPageProvider
     */
    public function testCanShowPage($quoteId, $orderId, $expected)
    {
        $instance = $this->_getInstance();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('__call')
            ->withConsecutive(array('getLastSuccessQuoteId'), array('getLastRealOrderId'))
            ->willReturnOnConsecutiveCalls($quoteId, $orderId);

        $result = $this->invokeMethod($instance, 'canShowPage');
        $this->assertEquals($expected, $result);
    }

    public function testGetOrderDoesntExist()
    {
        $instance = $this->_getInstance();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->expects($this->once())->method('__call')->with('getLastRealOrderId')->willReturn(123);

        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('loadByIncrementId'))
            ->getMock();
        $orderMock->expects($this->once())->method('loadByIncrementId')->with(123)->willReturnSelf();
        $this->setModelMock('sales/order', $orderMock);

        $result = $this->invokeMethod($instance, 'getOrder');
        $this->assertEquals($orderMock, $result);
    }

    public function testGetOrderExist()
    {
        $existingOrderMock = $this->getMockBuilder('Mage_Sales_Model_Order')->disableOriginalConstructor()->getMock();

        $instance = $this->_getInstance();
        $this->setProperty('_order', $existingOrderMock, $instance);

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->expects($this->never())->method('__call');

        $newOrderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('loadByIncrementId'))
            ->getMock();
        $newOrderMock->expects($this->never())->method('loadByIncrementId');
        $this->setModelMock('sales/order', $newOrderMock);

        $result = $this->invokeMethod($instance, 'getOrder');
        $this->assertEquals($existingOrderMock, $result);
    }

    public function testSendCancelRequest()
    {
        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('getPayment'))
            ->getMock();

        $paymentMock = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('setAdditionalInformation','save'))
            ->getMock();
        $paymentMock->expects($this->once())->method('setAdditionalInformation');
        $paymentMock->expects($this->once())->method('save');

        $orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));


        $cancelAuthorizeMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_CancelAuthorize')
            ->disableOriginalConstructor()
            ->setMethods(array('sendRequest'))
            ->getMock();
        $cancelAuthorizeMock->expects($this->once())->method('sendRequest');

        $this->setModelMock('buckaroo3extended/request_cancelAuthorize', $cancelAuthorizeMock);

        $instance = $this->_getInstance();
        $this->setProperty('_order', $orderMock, $instance);

        $this->invokeMethod($instance, 'sendCancelRequest');
    }

    public function testSendCancelRequestThrowsException()
    {
        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('setAdditionalInformation','save'))
            ->getMock();
        $paymentMock->expects($this->once())->method('setAdditionalInformation');
        $paymentMock->expects($this->once())->method('save');

        $orderMock->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($paymentMock));

        $cancelAuthorizeMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Request_CancelAuthorize')
            ->disableOriginalConstructor()
            ->setMethods(array('sendRequest'))
            ->getMock();
        $cancelAuthorizeMock->expects($this->once())->method('sendRequest')->willThrowException(new \Exception());

        $this->setModelMock('buckaroo3extended/request_cancelAuthorize', $cancelAuthorizeMock);

        $instance = $this->_getInstance();

        $this->setProperty('_order', $orderMock, $instance);
        $this->setExpectedException('Exception');

        $helperMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Helper_Data')
            ->setMethods(array('logException'))
            ->getMock();
        $helperMock->expects($this->once())->method('logException');
        $this->setHelperMock('buckaroo3extended', $helperMock);

        $this->invokeMethod($instance, 'sendCancelRequest');
    }

    public function testUpdateStatusHistory()
    {
        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('addStatusHistoryComment', 'save'))
            ->getMock();
        $orderMock->expects($this->once())
            ->method('addStatusHistoryComment')
            ->with('Your payment was unsuccessful, cancelled by consumer.');
        $orderMock->expects($this->once())->method('save');

        $instance = $this->_getInstance();
        $this->setProperty('_order', $orderMock, $instance);

        $this->invokeMethod($instance, 'updateStatusHistory');
    }

    public function testUpdateStatusHistoryThrowsException()
    {
        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('addStatusHistoryComment', 'save'))
            ->getMock();
        $orderMock->expects($this->once())->method('save')->willThrowException(new \Exception());

        $instance = $this->_getInstance();

        $this->setProperty('_order', $orderMock, $instance);
        $this->setExpectedException('Exception');

        $helperMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Helper_Data')
            ->setMethods(array('logException'))
            ->getMock();
        $helperMock->expects($this->once())->method('logException');
        $this->setHelperMock('buckaroo3extended', $helperMock);

        $this->invokeMethod($instance, 'updateStatusHistory');
    }

    public function testRestoreQuote()
    {
        $quoteId = 123;

        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('getQuoteId'))
            ->getMock();
        $orderMock->expects($this->once())->method('getQuoteId')->willReturn($quoteId);

        $quoteMock = $this->getMockBuilder('Mage_Sales_Model_Quote')
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'setIsActive', 'setReservedOrderId', 'save'))
            ->getMock();
        $quoteMock->expects($this->once())->method('load')->with($quoteId)->willReturnSelf();
        $quoteMock->expects($this->once())->method('setIsActive')->with(true)->willReturnSelf();
        $quoteMock->expects($this->once())->method('setReservedOrderId')->with(null)->willReturnSelf();
        $quoteMock->expects($this->once())->method('save')->willReturnSelf();

        $this->setModelMock('sales/quote', $quoteMock);

        $instance = $this->_getInstance();

        $session = Mage::getSingleton('checkout/session');
        $session->expects($this->once())->method('replaceQuote')->with($quoteMock);

        $this->setProperty('_order', $orderMock, $instance);
        $this->invokeMethod($instance, 'restoreQuote');
    }

    public function testAddErrorMessage()
    {
        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId'))
            ->getMock();
        $orderMock->expects($this->once())->method('getStoreId')->willReturn(1);

        $instance = $this->_getInstance();
        $this->setProperty('_order', $orderMock, $instance);

        $session = Mage::getSingleton('core/session');
        $session->expects($this->once())
            ->method('addError')
            ->with('Your payment was unsuccessful, cancelled by consumer.');

        $this->invokeMethod($instance, 'addErrorMessage');
    }

    public function testCancelOrder()
    {
        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId', 'getGiftCardsAmount', 'cancel', 'save'))
            ->getMock();
        $orderMock->expects($this->once())->method('getStoreId')->willReturn(1);
        $orderMock->expects($this->once())->method('getGiftCardsAmount')->willReturn(2);
        $orderMock->expects($this->once())->method('cancel')->willReturnSelf();
        $orderMock->expects($this->once())->method('save');

        $giftcardHelperMock = $this->getMockBuilder('Mage_Enterprise_Giftcardaccount_Helper_Data')
            ->setMethods(array('getCards'))
            ->getMock();
        $giftcardHelperMock->expects($this->once())->method('getCards')->with($orderMock)->willReturn(array());
        $this->setHelperMock('enterprise_giftcardaccount', $giftcardHelperMock);

        Mage::app()->getStore(1)->setConfig('buckaroo/buckaroo3extended_advanced/cancel_on_failed', true);

        $instance = $this->_getInstance();

        $this->setProperty('_order', $orderMock, $instance);
        $this->invokeMethod($instance, 'cancelOrder');
    }

    public function testCancelOrderThrowsException()
    {
        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId'))
            ->getMock();
        $orderMock->expects($this->once())->method('getStoreId')->willReturn(1);

        Mage::app()->getStore(1)->setConfig('buckaroo/buckaroo3extended_advanced/cancel_on_failed', true);

        $instance = $this->_getInstance();

        $this->setProperty('_order', $orderMock, $instance);
        $this->setExpectedException('Exception');

        $helperMock = $this->getMockBuilder('TIG_Buckaroo3Extended_Helper_Data')
            ->setMethods(array('logException'))
            ->getMock();
        $helperMock->expects($this->once())->method('logException');
        $this->setHelperMock('buckaroo3extended', $helperMock);

        $this->invokeMethod($instance, 'cancelOrder');
    }

    public function refundGiftcardsProvider()
    {
        return array(
            'not array' => array(
                null,
                false,
                false
            ),
            'no cards' => array(
                array(),
                false,
                false
            ),
            'card no authorization' => array(
                array(
                    array(
                        'i' => 456
                    )
                ),
                false,
                false
            ),
            'card not found' => array(
                array(
                    array(
                        'authorized' => true,
                        'i' => 0
                    )
                ),
                true,
                false
            ),
            'card reverted' => array(
                array(
                    array(
                        'authorized' => true,
                        'i' => 123
                    )
                ),
                true,
                true
            )
        );
    }

    /**
     * @param $cards
     * @param $canSearchCard
     * @param $cardFound
     *
     * @dataProvider refundGiftcardsProvider
     */
    public function testRefundGiftcards($cards, $canSearchCard, $cardFound)
    {
        $giftcardAccountMock = $this->getMockBuilder('Enterprise_GiftCardAccount_Model_Giftcardaccount')
            ->setMethods(array('load', 'revert', 'unsOrder', 'save'))
            ->getMock();
        $giftcardAccountMock->expects($this->exactly((int)$cardFound))->method('revert')->willReturnSelf();
        $giftcardAccountMock->expects($this->exactly((int)$cardFound))->method('unsOrder')->willReturnSelf();
        $giftcardAccountMock->expects($this->exactly((int)$cardFound))->method('save');

        $canSearchCard = ($canSearchCard ? $this->once() : $this->never());
        $giftcardAccountExpects = $giftcardAccountMock->expects($canSearchCard)->method('load');

        if ($cardFound) {
            $giftcardAccountExpects->willReturnSelf();
        }

        $this->setModelMock('enterprise_giftcardaccount/giftcardaccount', $giftcardAccountMock);

        $instance = $this->_getInstance();
        $this->invokeMethod($instance, 'refundGiftcards', array($cards));
    }
}