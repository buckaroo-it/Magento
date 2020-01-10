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
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentFee_Order_Creditmemo_Total_FeeTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentFee_Order_Creditmemo_Total_Fee */
    protected $_instance = null;

    /** @var null|Mage_Sales_Model_Order */
    protected $_order = null;

    /** @var null|Mage_Sales_Model_Order_Invoice */
    protected $_invoice = null;

    public function setUp()
    {
        $this->registerMockSessions(array('admin'));
    }

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentFee_Order_Creditmemo_Total_Fee();
        }

        return $this->_instance;
    }

    protected function _getMockOrder()
    {
        if ($this->_order === null) {
            $this->_order = $this->getMockBuilder('Mage_Sales_Model_Order')
                ->setMethods(
                    array(
                        'getBuckarooFee', 'getBaseBuckarooFee', 'getBuckarooFeeRefunded', 'getBaseBuckarooFeeRefunded'
                    )
                )
                ->getMock();
        }

        return $this->_order;
    }

    protected function _getMockInvoice()
    {
        if ($this->_invoice === null) {
            $this->_invoice = $this->getMockBuilder('Mage_Sales_Model_Order_Invoice')
                ->setMethods(array('getBuckarooFee', 'getBaseBuckarooFee'))
                ->getMock();
        }

        return $this->_invoice;
    }

    protected function _getMockCreditmemo()
    {
        $mockOrder = $this->_getMockOrder();
        $mockInvoice = $this->_getMockInvoice();

        $mockCreditmemo = $this->getMockBuilder('Mage_Sales_Model_Order_Creditmemo')
            ->setMethods(array('getOrder', 'getInvoice', 'getBuckarooFee', 'getBaseBuckarooFee'))
            ->getMock();
        $mockCreditmemo->expects($this->once())
            ->method('getOrder')
            ->willReturn($mockOrder);
        $mockCreditmemo->expects($this->once())
            ->method('getInvoice')
            ->willReturn($mockInvoice);

        return $mockCreditmemo;
    }

    /**
     * @return array
     */
    public function testCollectProvider()
    {
        return array(
            array(
                false,
                'never',
                array(
                    'fee' => 0,
                    'invoiceFee' => 0,
                    'orderFee' => 0,
                    'orderRefundFee' => 0,
                    'paramsFee' => 0
                ),
            ),
            array(
                false,
                'never',
                array(
                    'fee' => 1.23,
                    'invoiceFee' => 2.34,
                    'orderFee' => 0,
                    'orderRefundFee' => 0,
                    'paramsFee' => 0
                ),
            ),
            array(
                true,
                'once',
                array(
                    'fee' => 0,
                    'invoiceFee' => 3.45,
                    'orderFee' => 4.56,
                    'orderRefundFee' => 0,
                    'paramsFee' => 4.56
                ),
            ),
            array(
                false,
                'never',
                array(
                    'fee' => 0,
                    'invoiceFee' => 5.67,
                    'orderFee' => 7.89,
                    'orderRefundFee' => 6.78,
                    'paramsFee' => 0
                ),
            ),
            array(
                false,
                'never',
                array(
                    'fee' => 0,
                    'invoiceFee' => 8.90,
                    'orderFee' => 0,
                    'orderRefundFee' => 0,
                    'paramsFee' => 0
                ),
            )
        );
    }

    /**
     * @param $isAdmin
     * @param $isAdminExpects
     * @param $fee
     *
     * @dataProvider testCollectProvider
     */
    public function testCollect($isAdmin, $isAdminExpects, $fee)
    {
        $adminSession = Mage::getSingleton('admin/session');
        $adminSession->expects($this->$isAdminExpects())
            ->method('isLoggedIn')
            ->willReturn($isAdmin);

        $mockHelper = $this->getMockBuilder('TIG_Buckaroo3Extended_Helper_Data')
            ->setMethods(array('isAdmin'))
            ->getMock();
        $mockHelper->expects($this->any())
            ->method('isAdmin')
            ->willReturn($isAdmin);

        $request = Mage::app()->getRequest();
        $request->setParam('creditmemo', array('buckaroo_fee' => $fee['paramsFee']));

        $mockInvoice = $this->_getMockInvoice();
        $mockInvoice->expects($this->once())->method('getBuckarooFee')->willReturn($fee['invoiceFee']);
        $mockInvoice->expects($this->any())->method('getBaseBuckarooFee')->willReturn($fee['invoiceFee']);

        $mockOrder = $this->_getMockOrder();
        $mockOrder->expects($this->any())->method('getBuckarooFee')->willReturn($fee['orderFee']);
        $mockOrder->expects($this->any())->method('getBaseBuckarooFee')->willReturn($fee['orderFee']);
        $mockOrder->expects($this->any())->method('getBuckarooFeeRefunded')->willReturn($fee['orderRefundFee']);
        $mockOrder->expects($this->any())->method('getBaseBuckarooFeeRefunded')->willReturn($fee['orderRefundFee']);

        $mockCreditmemo = $this->_getMockCreditmemo();
        $mockCreditmemo->expects($this->once())->method('getBuckarooFee')->willReturn($fee['fee']);
        $mockCreditmemo->expects($this->once())->method('getBaseBuckarooFee')->willReturn($fee['fee']);

        $instance = $this->_getInstance();
        $this->setProperty('_helper', $mockHelper, $instance);

        $result = $instance->collect($mockCreditmemo);

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Model_PaymentFee_Order_Creditmemo_Total_Fee', $result);
    }
}
