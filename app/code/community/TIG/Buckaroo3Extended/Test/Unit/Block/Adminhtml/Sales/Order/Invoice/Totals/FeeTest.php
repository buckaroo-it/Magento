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
class TIG_Buckaroo3Extended_Test_Unit_Block_Adminhtml_Sales_Order_Invoice_Totals_FeeTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Invoice_Totals_Fee */
    protected $_instance = null;

    /** @var null|Mage_Sales_Model_Order_Invoice */
    protected $_invoice = null;

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $mockInvoice = $this->_getMockInvoice();

            $mockParentBlock = $this->getMockBuilder('Mage_Adminhtml_Block_Sales_Order_Invoice_Totals')
                ->setMethods(array('getInvoice', 'addTotalBefore'))
                ->getMock();
            $mockParentBlock->expects($this->once())
                ->method('getInvoice')
                ->willReturn($mockInvoice);
            $mockParentBlock->expects($this->any())
                ->method('addTotalBefore');

            $this->_instance = new TIG_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Invoice_Totals_Fee();
            $this->setProperty('_parentBlock', $mockParentBlock, $this->_instance);
        }

        return $this->_instance;
    }

    /**
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function _getMockInvoice()
    {
        if ($this->_invoice === null) {
            $mockOrder = $this->_getMockOrder();

            $this->_invoice = $this->getMockBuilder('Mage_Sales_Model_Order_Invoice')
                ->setMethods(
                    array(
                        'getOrder', 'getBuckarooFee', 'getBaseBuckarooFee', 'getBuckarooFeeTax', 'getBaseBuckarooFeeTax'
                    )
                )
                ->getMock();
        }

        return $this->_invoice;
    }

    /**
     * @return mixed
     */
    protected function _getMockOrder()
    {
        $mockPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(array('getMethod'))
            ->getMock();
        $mockPayment->expects($this->any())
            ->method('getMethod')
            ->willReturn('buckaroo3extended_afterpay');

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getPayment'))
            ->getMock();
        $mockOrder->expects($this->any())
            ->method('getPayment')
            ->willReturn($mockPayment);

        return $mockOrder;
    }

    /**
     * @return array
     */
    public function testInitTotalsDataprovider()
    {
        return array(
            array(
                '2',
                'once',
                'once',
                TIG_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Invoice_Totals_Fee::DISPLAY_MODE_BOTH
            ),
            array(
                '3.14',
                'never',
                'once',
                TIG_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Invoice_Totals_Fee::DISPLAY_MODE_EXCL
            ),
            array(
                '1.19',
                'once',
                'once',
                TIG_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Invoice_Totals_Fee::DISPLAY_MODE_INCL
            ),
            array(
                '0',
                'never',
                'never',
                null
            )
        );
    }

    /**
     * @param $buckarooFee
     * @param $buckarooFeeTaxExpects
     * @param $orderExpects
     * @param $displayMode
     *
     * @dataProvider testInitTotalsDataprovider
     */
    public function testInitTotals(
        $buckarooFee,
        $buckarooFeeTaxExpects,
        $orderExpects,
        $displayMode
    ) {
        $mockOrder = $this->_getMockOrder();
        $mockInvoice = $this->_getMockInvoice();

        $mockInvoice->expects($this->$buckarooFeeTaxExpects())->method('getBuckarooFeeTax');
        $mockInvoice->expects($this->$buckarooFeeTaxExpects())->method('getBaseBuckarooFeeTax');
        $mockInvoice->expects($this->$orderExpects())->method('getOrder')->willReturn($mockOrder);

        $store = Mage::app()->getStore();
        $store->setConfig(
            TIG_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Invoice_Totals_Fee::XPATH_DISPLAY_MODE_BUCKAROO_FEE,
            $displayMode
        );

        $mockInvoice->expects($this->once())
            ->method('getBuckarooFee')
            ->willReturn($buckarooFee);
        $mockInvoice->expects($this->once())
            ->method('getBaseBuckarooFee')
            ->willReturn($buckarooFee);

        $instance = $this->_getInstance();
        $result = $instance->initTotals();

        $this->assertInstanceOf('TIG_Buckaroo3Extended_Block_Adminhtml_Sales_Order_Invoice_Totals_Fee', $result);
    }
}
