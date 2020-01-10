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
 * to support@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Afterpay20_PaymentMethodTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_PaymentMethod */
    protected $_instance = null;

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
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

    /**
     * @return TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_PaymentMethod
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_PaymentMethod();
        }

        return $this->_instance;
    }

    public function testValidate()
    {
        $mockPaymentInfo = $this->_getMockPayment();

        $instance = $this->_getInstance();
        $instance->setData('info_instance', $mockPaymentInfo);

        $postData = array(
            $instance->getCode() . '_bpe_accept'                  => 'checked',
            $instance->getCode() . '_BPE_Customergender'          => 1,
            $instance->getCode() . '_bpe_customer_phone_number'   => '0513744112',
            $instance->getCode() . '_bpe_customer_idnumber'   => '190122-829F',
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

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Afterpay20_PaymentMethod', $result);
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
                    'Status' => (Object)array(
                        'SubCode' => (Object)array(
                            '_' => 'Error from Payment Plaza'
                        )
                    )
                ),
                'Error from Payment Plaza'
            ),
            'has no rejected message' => array(
                (Object)array(
                    'Status' => (Object)array(
                        'SubCode' => (Object)array(
                            '_' => ''
                        )
                    )
                ),
                false
            ),
            'has no value' => array(
                (Object)array(
                    'Status' => (Object)array(
                        'SubCode' => (Object)array(
                            'Name' => 'error'
                        )
                    )
                ),
                false
            ),
            'has no SubCode' => array(
                (Object)array(
                    'Status' => (Object)array(
                        'someOtherObject' => 'TIG response'
                    )
                ),
                false
            ),
            'has no Status' => array(
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