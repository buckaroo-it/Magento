<?php
class TIG_Buckaroo3Extended_Model_Refund_CreditmemoTest extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    public function createInstanceWithoutConstructor($class)
    {
        $reflector = new ReflectionClass($class);

        if (method_exists($class, 'newInstanceWithoutConstructor')) {
            return $class->newInstanceWithoutConstructor();
        }

        $properties = $reflector->getProperties();
        $defaults = $reflector->getDefaultProperties();

        $serealized = "O:" . strlen($class) . ":\"$class\":".count($properties) .':{';

        foreach ($properties as $property) {
            $name = $property->getName();
            if ($property->isProtected()) {
                $name = '*' . $name;
            } elseif ($property->isPrivate()) {
                $name = $class . $name;
            }

            $serealized .= serialize($name);

            if (array_key_exists($property->getName(), $defaults)) {
                $serealized .= serialize($defaults[$property->getName()]);
            } else {
                $serealized .= serialize(null);
            }
        }

        $serealized .="}";

        // @codingStandardsIgnoreLine
        return unserialize($serealized);
    }

    protected function _getInstance($data = array())
    {
        $creditmemo = $this->createInstanceWithoutConstructor('TIG_Buckaroo3Extended_Model_Refund_Creditmemo');

        if (array_key_exists('order', $data)) {
            $this->invokeMethod($creditmemo, 'setCurrentOrder', array($data['order']));
        }

        if (!array_key_exists('storeId', $data)) {
            $order =  $this->invokeMethod($creditmemo, 'getOrder');
            $this->invokeMethod($creditmemo, 'setStoreId', array($order->getStoreId()));
        } else {
            $this->invokeMethod($creditmemo, 'setStoreId', array($data['storeId']));
        }

        if (array_key_exists('payment', $data)) {
            $this->invokeMethod($creditmemo, '', array($data['payment']));
        }

        if (array_key_exists('postArray', $data)) {
            $this->invokeMethod($creditmemo, 'setPostArray', array($data['postArray']));
        }

        if (array_key_exists('XML', $data)) {
            $this->invokeMethod($creditmemo, 'setResponseXML', array($data['XML']));
        }

        if (array_key_exists('response', $data)) {
            $this->invokeMethod($creditmemo, 'setResponse', array($data['response']));
        }

        if (array_key_exists('debugEmail', $data)) {
            $this->invokeMethod($creditmemo, 'setDebugEmail', array($data['debugEmail']));
        }

        return $creditmemo;
    }

    public function _getCreditmemoDataItemsTestProvider()
    {
        return array(
            array(1, 99),
            array(666, 99),
        );
    }

    /**
     * @test
     * @dataProvider _getCreditmemoDataItemsTestProvider
     */
    public function _getCreditmemoDataItemsTest($id, $qty)
    {
        $postArray = array('brq_amount_credit'=>3);

        // Create the mock order item.
        $mockMageOrderItem = $this->getMockBuilder('Mage_Sales_Model_Order_Item')
            ->setMethods(array('getId', 'getQtyRefunded', 'getQtyInvoiced'))
            ->getMock();
        $mockMageOrderItem->method('getId')->will($this->returnValue($id));
        $mockMageOrderItem->method('getQtyRefunded')->will($this->returnValue($qty));
        $mockMageOrderItem->method('getQtyInvoiced')->will($this->returnValue($qty));

        // Create the mock order.
        $mockMageOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getId', 'getAllItems'))
            ->getMock();
        $mockMageOrder->method('getId')->will($this->returnValue(1));
        $mockMageOrder->method('getAllItems')->will($this->returnValue(array($mockMageOrderItem)));

        $creditmemo = $this->_getInstance(
            array(
                'postArray'=>$postArray,
                'order'=>$mockMageOrder,
                )
        );
        $result = $this->invokeMethod($creditmemo, '_getCreditmemoDataItems');

        $this->assertArrayHasKey($id, $result);
    }
}
