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
class TIG_Buckaroo3Extended_Block_PaymentMethods_Payconiq_Checkout_PayTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Block_PaymentMethods_Payconiq_Checkout_Pay */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions('checkout');
    }

    /**
     * @return TIG_Buckaroo3Extended_Block_PaymentMethods_Payconiq_Checkout_Pay
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Block_PaymentMethods_Payconiq_Checkout_Pay();
        }

        return $this->_instance;
    }

    public function testGetTransactionKey()
    {
        $orderId = 123;
        $transactionKey = 'key123abc';

        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('loadByIncrementId', 'getTransactionKey'))
            ->getMock();
        $orderMock->expects($this->once())->method('loadByIncrementId')->with($orderId)->willReturnSelf();
        $orderMock->expects($this->once())->method('getTransactionKey')->willReturn($transactionKey);

        $this->setModelMock('sales/order', $orderMock);

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->expects($this->once())->method('__call')->with('getLastRealOrderId')->willReturn($orderId);

        $instance = $this->_getInstance();
        $result = $instance->getTransactionKey();
        $this->assertEquals($transactionKey, $result);
    }

    public function testGetCancelUrl()
    {
        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('loadByIncrementId'))
            ->getMock();
        $orderMock->expects($this->once())->method('loadByIncrementId')->willReturnSelf();
        $this->setModelMock('sales/order', $orderMock);

        $instance = $this->_getInstance();
        $result = $instance->getCancelUrl();
        $this->assertStringEndsWith('buckaroo3extended/payconiq/cancel/', $result);
    }

    public function testGetCancelMessage()
    {
        $orderMock = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->setMethods(array('loadByIncrementId'))
            ->getMock();
        $orderMock->expects($this->once())->method('loadByIncrementId')->willReturnSelf();
        $this->setModelMock('sales/order', $orderMock);

        $instance = $this->_getInstance();
        $result = $instance->getCancelMessage();
        $this->assertInternalType('string', $result);
    }
}
