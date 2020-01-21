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
class Buckaroo_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Klarna_PaymentMethodTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Klarna_PaymentMethod */
    protected $_instance = null;

    /**
     * @return null|Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Klarna_PaymentMethod
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Klarna_PaymentMethod();
        }

        return $this->_instance;
    }

    /**
     * @return mixed
     */
    protected function _getMockPayment()
    {
        $mockOrderAddress = $this->getMockBuilder('Mage_Sales_Model_Order_Address')->getMock();

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

    public function testGetAllowedCurrencies()
    {
        $instance = $this->_getInstance();
        $result = $instance->getAllowedCurrencies();

        $this->assertInternalType('array', $result);
        $this->assertContains('EUR', $result);
    }

    public function testGetCode()
    {
        $instance = $this->_getInstance();
        $result = $instance->getCode();

        $this->assertEquals('buckaroo3extended_klarna', $result);
    }

    public function testGetFormBlockType()
    {
        $instance = $this->_getInstance();
        $result = $instance->getFormBlockType();

        $this->assertEquals('buckaroo3extended/paymentMethods_klarna_checkout_form', $result);
    }

    public function testGetOrderPlaceRedirectUrl()
    {
        $postArray = array(
            'payment' => array(
                'buckaroo3extended_klarna' => array(
                    'year' => '1970',
                    'month' => '07',
                    'day' => '10'
                )
            ),
            'buckaroo3extended_klarna_BPE_Customergender' => 1,
            'buckaroo3extended_klarna_bpe_customer_phone_number' => '0612345678',
        );

        Mage::app()->getRequest()->setPost($postArray);

        $instance = $this->_getInstance();
        $functionResult = $instance->getOrderPlaceRedirectUrl();

        $this->assertInternalType('string', $functionResult);
    }

    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        return array(
            'payment_action field' => array(
                'payment_action',
                Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE
            ),
            'any other field' => array(
                'some_config_field',
                null
            )
        );
    }

    /**
     * @param $field
     * @param $expected
     *
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfigData($field, $expected)
    {
        $instance = $this->_getInstance();
        $result = $instance->getConfigData($field);

        $this->assertEquals($expected, $result);
    }

    public function testCapture()
    {
        $mockPaymentInfo = $this->_getMockPayment();
        $instance = $this->_getInstance();
        $instance->setData('info_instance', $mockPaymentInfo);

        $result = $instance->capture($mockPaymentInfo, 0);

        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentMethods_Klarna_PaymentMethod', $result);
    }

    /**
     * @return array
     */
    public function canInvoicePartiallyProvider()
    {
        return array(
            'full invoice, no discount'         => array(0, 1, 0, 1, true),
            'partial invoice, no discount'      => array(0, 2, 0, 3, true),
            'full invoice, order discount'      => array(0, 4, 5, 4, true),
            'partial invoice, order discount'   => array(0, 5, 6, 7, false),
            'full invoice, invoice discount'    => array(8, 9, 0, 9, true),
            'partial invoice, invoice discount' => array(10, 11, 0, 12, false),
            'full invoice, both discount'       => array(13, 14, 13, 14, true),
            'partial invoice, both discount'    => array(15, 16, 15, 17, false),
        );
    }

    /**
     * @param $invoiceDiscount
     * @param $invoiceTotal
     * @param $orderDiscount
     * @param $orderTotal
     * @param $expected
     *
     * @dataProvider canInvoicePartiallyProvider
     */
    public function testCanInvoicePartially($invoiceDiscount, $invoiceTotal, $orderDiscount, $orderTotal, $expected)
    {
        $mockInvoice = $this->getMockBuilder('Mage_Sales_Model_Order_Invoice')
            ->setMethods(array('getLastItem', 'getDiscountAmount', 'getBaseGrandTotal'))
            ->getMock();
        $mockInvoice->expects($this->once())->method('getLastItem')->willReturnSelf();
        $mockInvoice->expects($this->once())->method('getDiscountAmount')->willReturn($invoiceDiscount);
        $mockInvoice->expects($this->once())->method('getBaseGrandTotal')->willReturn($invoiceTotal);

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getInvoiceCollection', 'getDiscountAmount', 'getBaseGrandTotal'))
            ->getMock();
        $mockOrder->expects($this->once())->method('getInvoiceCollection')->willReturn($mockInvoice);
        $mockOrder->expects($this->once())->method('getDiscountAmount')->willReturn($orderDiscount);
        $mockOrder->expects($this->once())->method('getBaseGrandTotal')->willReturn($orderTotal);

        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getOrder'))
            ->getMock();
        $mockPayment->expects($this->any())
            ->method('getOrder')
            ->willReturn($mockOrder);

        $instance = $this->_getInstance();
        $instance->setData('info_instance', $mockPayment);

        $result = $instance->canInvoicePartially();
        $this->assertEquals($expected, $result);
    }
}
