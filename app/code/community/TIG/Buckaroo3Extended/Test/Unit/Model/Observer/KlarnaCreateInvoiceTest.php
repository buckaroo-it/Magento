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
class TIG_Buckaroo3Extended_Test_Unit_Model_Observer_KlarnaCreateInvoiceTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_Observer_KlarnaCreateInvoice  */
    protected $_instance = null;

    /**
     * Init instance
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_Observer_KlarnaCreateInvoice();
        }

        return $this->_instance;
    }

    public function testIsKlarnaPayment()
    {
        $order = $this->getOrderMock();

        $instance = $this->_getInstance();
        $result = $this->invokeMethod(
            $instance,
            '_isKlarnaPayment',
            array($order)
        );

        $this->assertEquals(true, $result);
    }

    public function testGetQtysShipped()
    {
        $shipment   = $this->getShipmentMock();
        $orderItems = $this->getOrderItemsMock();

        $instance = $this->_getInstance();
        $result = $this->invokeMethod(
            $instance,
            '_getQtysShipped',
            array($shipment, array($orderItems))
        );

        $this->assertEquals(array(11 => 1), $result);
    }


    protected function getShipmentMock()
    {
        $mockShipment = $this->getMockBuilder('Mage_Sales_Model_Order_Shipment')
            ->setMethods(array('getStoreId', 'getAllItems', 'getOrder'))->getMock();
        $mockShipment->method('getStoreId')->will($this->returnValue(1));
        $mockShipment->method('getAllItems')->will($this->returnValue(array($this->getShipmentItemsMock())));
        $mockShipment->method('getOrder')->will($this->returnValue($this->getOrderMock()));

        return $mockShipment;
    }

    protected function getOrderMock()
    {
        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getStoreId', 'getAllItems', 'getPayment'))->getMock();
        $mockOrder->method('getStoreId')->will($this->returnValue(1));
        $mockOrder->method('getAllItems')->will($this->returnValue(array($this->getOrderItemsMock())));
        $mockOrder->method('getPayment')->will($this->returnValue($this->getPaymentMock()));

        return $mockOrder;
    }

    protected function getShipmentItemsMock()
    {
        $mockShipmentItem = $this->getMockBuilder('Mage_Sales_Model_Order_Shipment_Item')
            ->setMethods(array('getId', 'getQty', 'getSku'))->getMock();
        $mockShipmentItem->method('getId')->will($this->returnValue(10));
        $mockShipmentItem->method('getQty')->will($this->returnValue(1));
        $mockShipmentItem->method('getSku')->will($this->returnValue('SKU1'));

        return $mockShipmentItem;
    }

    protected function getOrderItemsMock()
    {
        $mockOrderItem = $this->getMockBuilder('Mage_Sales_Model_Order_Item')
            ->setMethods(array('getId', 'getQty', 'getSku'))->getMock();
        $mockOrderItem->method('getId')->will($this->returnValue(11));
        $mockOrderItem->method('getQty')->will($this->returnValue(2));
        $mockOrderItem->method('getSku')->will($this->returnValue('SKU1'));

        return $mockOrderItem;
    }

    protected function getPaymentMock()
    {
        $mockKlarnaMethod = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_PaymentMethods_Klarna_PaymentMethod')
            ->setMethods(array('getCode'))->getMock();
        $mockKlarnaMethod->method('getCode')->will($this->returnValue('buckaroo3extended_klarna'));

        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethodInstance'))->getMock();
        $mockPayment->method('getMethodInstance')->will($this->returnValue($mockKlarnaMethod));

        return $mockPayment;
    }
}
