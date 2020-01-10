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
class TIG_Buckaroo3Extended_Test_Unit_Model_Response_PushTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_Response_Push */
    protected $_instance = null;

    /**
     * @return null|TIG_Buckaroo3Extended_Model_Response_Push
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_Response_Push();
        }

        return $this->_instance;
    }

    /**
     * @return array
     */
    public function sendInvoiceEmailProvider()
    {
        return array(
            'no transaction' => array(
                null,
                1,
                false
            ),
            'no invoice found' => array(
                'abc',
                0,
                false
            ),
            'email already sent' => array(
                'def',
                2,
                true
            ),
            'email should be sent' => array(
                'ghi',
                3,
                false
            )
        );
    }

    /**
     * @param $transaction
     * @param $invoiceId
     * @param $emailSent
     *
     * @dataProvider sendInvoiceEmailProvider
     */
    public function testSendInvoiceEmail($transaction, $invoiceId, $emailSent)
    {
        $expectHasTransaction = (int)((bool)$transaction);
        $expectInvoiceFound = (int)($expectHasTransaction && $invoiceId);
        $expectEmailSend = (int)($expectInvoiceFound && !$emailSent);

        $mockInvoice = $this->getMockBuilder('Mage_Sales_Model_Order_Invoice')
            ->setMethods(array('getId', 'getEmailSent', 'sendEmail', 'setEmailSent', 'save'))
            ->getMock();
        $mockInvoice->method('getId')->willReturn($invoiceId);
        $mockInvoice->expects($this->exactly($expectInvoiceFound))->method('getEmailSent')->willReturn($emailSent);
        $mockInvoice->expects($this->exactly($expectEmailSend))->method('sendEmail')->willReturnSelf();
        $mockInvoice->expects($this->exactly($expectEmailSend))->method('setEmailSent')->willReturnSelf();
        $mockInvoice->expects($this->exactly($expectEmailSend))->method('save');

        $mockInvoiceCollection = $this->getMockBuilder('Mage_Sales_Model_Resource_Order_Invoice_Collection')
            ->setMethods(array('addFieldToFilter', 'setOrder', 'getFirstItem'))
            ->getMock();
        $mockInvoiceCollection->expects($this->exactly($expectHasTransaction))
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $mockInvoiceCollection->expects($this->exactly($expectHasTransaction))->method('setOrder')->willReturnSelf();
        $mockInvoiceCollection->expects($this->exactly($expectHasTransaction))
            ->method('getFirstItem')
            ->willReturn($mockInvoice);

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getInvoiceCollection'))
            ->getMock();
        $mockOrder->expects($this->exactly($expectHasTransaction))
            ->method('getInvoiceCollection')
            ->willReturn($mockInvoiceCollection);

        $instance = $this->_getInstance();
        $instance->setOrder($mockOrder);
        $instance->setPostArray(array('brq_transactions' => $transaction));

        $this->invokeMethod($instance, 'sendInvoiceEmail');
    }

    /**
     * @return array
     */
    public function getNewestInvoiceCommentProvider()
    {
        return array(
            'no comment items' => array(
                0,
                'abc',
                ''
            ),
            'empty comment text' => array(
                1,
                '',
                ''
            ),
            'with comment' => array(
                2,
                'def',
                'def'
            )
        );
    }

    /**
     * @param $id
     * @param $comment
     * @param $expected
     *
     * @dataProvider getNewestInvoiceCommentProvider
     */
    public function testGetNewestInvoiceComment($id, $comment, $expected)
    {
        $commentsMock = $this->getMockBuilder('Mage_Sales_Model_Order_Invoice_Comment')
            ->setMethods(array('getId', 'getComment'))
            ->getMock();
        $commentsMock->expects($this->once())->method('getId')->willReturn($id);
        $commentsMock->expects($this->exactly(($id ? 1 : 0)))->method('getComment')->willReturn($comment);

        $commentsCollectionMock = $this->getMockBuilder('Mage_Sales_Model_Resource_Order_Invoice_Comment_Collection')
            ->setMethods(array('getFirstItem'))
            ->getMock();
        $commentsCollectionMock->expects($this->once())->method('getFirstItem')->willReturn($commentsMock);
        
        $invoiceMock = $this->getMockBuilder('Mage_Sales_Model_Order_Invoice')
            ->setMethods(array('getCommentsCollection'))
            ->getMock();
        $invoiceMock->expects($this->once())->method('getCommentsCollection')->willReturn($commentsCollectionMock);

        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'getNewestInvoiceComment', array($invoiceMock));

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function decodePushValueProvider()
    {
        return array(
            'normal value' => array(
                'brq_key_data',
                'some value',
                'some value'
            ),
            'enccoded value' => array(
                'brq_timestamp',
                '14%3a19%3a53',
                '14:19:53'
            ),
            'payconiq_PayconiqAndroidUrl' => array(
                'brq_SERVICE_payconiq_PayconiqAndroidUrl',
                'http://tig.nl',
                'http://tig.nl'
            ),
            'payconiq_PayconiqIosUrl' => array(
                'brq_SERVICE_payconiq_PayconiqIosUrl',
                'http://tig.nl',
                'http://tig.nl'
            ),
            'payconiq_PayconiqUrl' => array(
                'brq_SERVICE_payconiq_PayconiqUrl',
                'http://tig.nl',
                'http://tig.nl'
            ),
            'payconiq_QrUrl' => array(
                'brq_SERVICE_payconiq_QrUrl',
                'http://tig.nl',
                'http://tig.nl'
            ),
            'masterpass_CustomerPhoneNumber' => array(
                'brq_SERVICE_masterpass_CustomerPhoneNumber',
                '+31201122233',
                '+31201122233'
            ),
            'masterpass_ShippingRecipientPhoneNumber' => array(
                'brq_SERVICE_masterpass_ShippingRecipientPhoneNumber',
                '+31644455566',
                '+31644455566'
            ),
            'InvoiceDate' => array(
                'brq_InvoiceDate',
                '2017-12-11T00:00:00.0000000+01:00',
                '2017-12-11T00:00:00.0000000+01:00'

            ),
            'DueDate' => array(
                'brq_DueDate',
                '2017-12-12T00:00:00.0000000+01:00',
                '2017-12-12T00:00:00.0000000+01:00'
            ),
            'PreviousStepDateTime' => array(
                'brq_PreviousStepDateTime',
                '0001-01-01T00:00:00.0000000+01:00',
                '0001-01-01T00:00:00.0000000+01:00'
            ),
            'EventDateTime' => array(
                'brq_EventDateTime',
                '2017-12-11T14:19:53.4688849+01:00',
                '2017-12-11T14:19:53.4688849+01:00'
            )
        );
    }

    /**
     * @param $brqKey
     * @param $brqValue
     * @param $expected
     *
     * @dataProvider decodePushValueProvider
     */
    public function testDecodePushValue($brqKey, $brqValue, $expected)
    {
        $instance = $this->_getInstance();
        $result = $this->invokeMethod($instance, 'decodePushValue', array($brqKey, $brqValue));
        $this->assertEquals($expected, $result);
    }
}
