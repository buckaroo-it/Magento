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
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Pospayment_ObserverTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_Observer */
    protected $_instance = null;

    /**
     * @return TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_Observer
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_Observer();
        }

        return $this->_instance;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Mage_Sales_Model_Order
     */
    protected function getMockOrder()
    {
        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethod'))
            ->getMock();
        $mockPayment->expects($this->any())->method('getMethod')->willReturn('buckaroo3extended_pospayment');

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getPayment','getPaymentMethodUsedForTransaction', 'getInvoiceCollection'))->getMock();
        $mockOrder->expects($this->any())->method('getPayment')->will($this->returnValue($mockPayment));
        $mockOrder->expects($this->any())->method('getPaymentMethodUsedForTransaction')->willReturn(false);

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
            ->setMethods(array('getRequest', 'getOrder'))
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

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_Observer', $result);
        $this->assertEquals('pospayment', $requestMethodResult);
    }

    public function testBuckaroo3extended_request_addservices()
    {
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_addservices($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_Observer', $result);
        $this->assertEquals('Pay', $requestVarsResult['services']['pospayment']['action']);
    }

    /**
     * @return array
     */
    public function buckaroo3extended_request_addcustomvarsProvider()
    {
        return array(
            array(
                'abcd1234',
                array(
                    'customVars' => array(
                        'pospayment' => array(
                            'TerminalID' => 'abcd1234'
                        )
                    )
                )
            ),
            array(
                'ef56gh78',
                array(
                    'customVars' => array(
                        'pospayment' => array(
                            'TerminalID' => 'ef56gh78'
                        )
                    )
                )
            ),
            array(
                '9021ijkl',
                array(
                    'customVars' => array(
                        'pospayment' => array(
                            'TerminalID' => '9021ijkl'
                        )
                    )
                )
            ),
        );
    }

    /**
     * @param $terminalid
     * @param $expected
     *
     * @dataProvider buckaroo3extended_request_addcustomvarsProvider
     */
    public function testBuckaroo3extended_request_addcustomvars($terminalid, $expected)
    {
        // @codingStandardsIgnoreLine
        $_COOKIE['Pos-Terminal-Id'] = $terminalid;
        $mockObserver = $this->getMockObserver();

        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_request_addcustomvars($mockObserver);
        $requestVarsResult = $mockObserver->getRequest()->getVars();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_Observer', $result);
        $this->assertEquals($expected, $requestVarsResult);
    }


    /**
     * @return array
     */
    public function buckaroo3extended_push_custom_save_invoice_afterProvider()
    {
        return array(
            'succes status' => array(
                TIG_Buckaroo3Extended_Model_Abstract::BUCKAROO_SUCCESS,
                1
            ),
            'failed status' => array(
                TIG_Buckaroo3Extended_Model_Abstract::BUCKAROO_FAILED,
                0
            ),
        );
    }

    /**
     * @param $status
     * @param $expectedCallCount
     *
     * @dataProvider buckaroo3extended_push_custom_save_invoice_afterProvider
     */
    public function testBuckaroo3extended_push_custom_save_invoice_after($status, $expectedCallCount)
    {
        $mockOrder = $this->getMockOrder();

        $mockPush = $this->getMockBuilder('TIG_Buckaroo3Extended_Model_Response_Push')
            ->setMethods(array('getPostArray'))
            ->getMock();
        $mockPush->expects($this->exactly($expectedCallCount))->method('getPostArray')->willReturn(array());

        $mockObserver = $this->getMockBuilder('Varien_Event_Observer')
            ->setMethods(array('getPush', 'getOrder', 'getResponse'))
            ->getMock();
        $mockObserver->expects($this->exactly($expectedCallCount))->method('getPush')->willReturn($mockPush);
        $mockObserver->expects($this->atLeastOnce())->method('getOrder')->willReturn($mockOrder);
        $mockObserver->expects($this->once())->method('getResponse')->willReturn(array('status' => $status));


        $instance = $this->_getInstance();
        $result = $instance->buckaroo3extended_push_custom_save_invoice_after($mockObserver);
        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_Observer', $result);
    }

    public function saveTicketToInvoiceProvider()
    {
        return array(
            'no ticket' => array(
                array('brq_transactions' => '123'),
                1,
                null
            ),
            'no invoices' => array(
                array(
                    'brq_SERVICE_pospayment_Ticket' => 'cba',
                    'brq_transactions' => '321'
                ),
                0,
                null
            ),
            'not encoded ticket' => array(
                array(
                    'brq_SERVICE_pospayment_Ticket' => 'abc',
                    'brq_transactions' => '456'
                ),
                1,
                'abc'
            ),
            'encoded ticket' => array(
                array(
                    'brq_SERVICE_pospayment_Ticket' => 'def+ghi',
                    'brq_transactions' => '789'
                ),
                1,
                'def ghi'
            ),
            'ticket with line breaks' => array(
                array(
                    'brq_SERVICE_pospayment_Ticket' => "jkl\r\nmno",
                    'brq_transactions' => '012'
                ),
                1,
                "jkl<br />\r\nmno"
            ),
            'encoded ticket with line breaks' => array(
                array(
                    'brq_SERVICE_pospayment_Ticket' => 'pqr%0Dstu+vwx',
                    'brq_transactions' => '345'
                ),
                1,
                "pqr<br />\rstu vwx"
            ),
        );
    }

    /**
     * @param $push
     * @param $invoiceCount
     * @param $expectedCommentToSave
     *
     * @dataProvider saveTicketToInvoiceProvider
     */
    public function testSaveTicketToInvoice($push, $invoiceCount, $expectedCommentToSave)
    {
        $hasTicket = (int)isset($push['brq_SERVICE_pospayment_Ticket']);
        $hasInvoices = (int)($hasTicket && $invoiceCount);

        $mockInvoice = $this->getMockBuilder('Mage_Sales_Model_Order_Invoice')
            ->setMethods(array('addComment', 'save'))
            ->getMock();
        $mockInvoice->expects($this->exactly($hasInvoices))
            ->method('addComment')
            ->with($expectedCommentToSave, true, true)
            ->willReturnSelf();
        $mockInvoice->expects($this->exactly($hasInvoices))->method('save');

        $mockInvoiceCollection = $this->getMockBuilder('Mage_Sales_Model_Resource_Order_Invoice_Collection')
            ->setMethods(array('addFieldToFilter', 'setOrder', 'count', 'getFirstItem'))
            ->getMock();
        $mockInvoiceCollection->expects($this->exactly($hasTicket))
            ->method('addFieldToFilter')
            ->with('transaction_id', array('eq' => $push['brq_transactions']))
            ->willReturnSelf();
        $mockInvoiceCollection->expects($this->exactly($hasTicket))->method('setOrder')->willReturnSelf();
        $mockInvoiceCollection->expects($this->exactly($hasTicket))->method('count')->willReturn($invoiceCount);
        $mockInvoiceCollection->expects($this->exactly($hasInvoices))->method('getFirstItem')->willReturn($mockInvoice);

        $mockOrder = $this->getMockOrder();
        $mockOrder->expects($this->exactly($hasTicket))
            ->method('getInvoiceCollection')
            ->willReturn($mockInvoiceCollection);

        $instance = $this->_getInstance();
        $this->invokeMethod($instance, 'saveTicketToInvoice', array($mockOrder, $push));
    }
}
