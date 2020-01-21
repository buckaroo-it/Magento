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
class Buckaroo_Buckaroo3Extended_Test_Unit_Helper_Data extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Helper_Data */
    protected $_instance = null;

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Buckaroo_Buckaroo3Extended_Helper_Data();
        }

        return $this->_instance;
    }

    /**
     * @return array
     */
    public function testIsOneStepCheckoutProvider()
    {
        return array(
            array(
                'onestepcheckout',
                true
            ),
            array(
                'onepage',
                false
            )
        );
    }

    /**
     * @param $moduleName
     * @param $expected
     *
     * @dataProvider testIsOneStepCheckoutProvider
     */
    public function testIsOneStepCheckout($moduleName, $expected)
    {
        $request = Mage::app()->getRequest();
        $request->setModuleName($moduleName);

        $instance = $this->_getInstance();

        $result = $instance->isOneStepCheckout();
        $this->assertEquals($expected, $result);
    }

    public function testGetFeeLabelProvider()
    {
        return array(
            array(
                false,
                'Fee'
            ),
            array(
                'buckaroo_unittest',
                'Unittest Fee Tax'
            )
        );
    }

    /**
     * @param $paymentCode
     * @param $feeLabel
     *
     * @dataProvider testGetFeeLabelProvider
     */
    public function testGetFeeLabel($paymentCode, $feeLabel)
    {
        Mage::app()->getStore()->setConfig('buckaroo/' . $paymentCode . '/payment_fee_label', $feeLabel);

        $instance = $this->_getInstance();

        $result = $instance->getFeeLabel($paymentCode);
        $this->assertEquals($feeLabel, $result);
    }

    public function testGetBuckarooFeeLabelProvider()
    {
        return array(
            array(
                false,
                'Buckaroo Fee'
            ),
            array(
                'buckaroo_unittest',
                'Unittest Fee Tax'
            )
        );
    }

    /**
     * @param $paymentCode
     * @param $feeLabel
     *
     * @dataProvider testGetBuckarooFeeLabelProvider
     */
    public function testGetBuckarooFeeLabel($paymentCode, $feeLabel)
    {
        Mage::app()->getStore()->setConfig('buckaroo/' . $paymentCode . '/payment_fee_label', $feeLabel);

        $instance = $this->_getInstance();

        $result = $instance->getBuckarooFeeLabel(null, $paymentCode);
        $this->assertEquals($feeLabel, $result);
    }

    /**
     * @return array
     */
    public function testCheckSellersProtectionProvider()
    {
        return array(
            array(
                false,
                false,
                false,
                false
            ),
            array(
                true,
                false,
                false,
                false
            ),
            array(
                true,
                true,
                true,
                false
            ),
            array(
                true,
                true,
                false,
                true
            )
        );
    }

    /**
     * @param $active
     * @param $sellerProtection
     * @param $isVirtual
     * @param $expected
     *
     * @dataProvider testCheckSellersProtectionProvider
     */
    public function testCheckSellersProtection($active, $sellerProtection, $isVirtual, $expected)
    {
        $store = Mage::app()->getStore();
        $store->setConfig('buckaroo/buckaroo3extended_paypal/active', $active);
        $store->setConfig('buckaroo/buckaroo3extended_paypal/sellers_protection', $sellerProtection);

        $mockOrder = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(array('getStoreId', 'getIsVirtual'))
            ->getMock();
        $mockOrder->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->willReturn($store->getId());
        $mockOrder->expects($this->any())
            ->method('getIsVirtual')
            ->willReturn($isVirtual);

        $instance = $this->_getInstance();
        $result = $instance->checkSellersProtection($mockOrder);

        $this->assertEquals($expected, $result);
    }

    public function testGetServiceModel()
    {
        $instance = $this->_getInstance();

        $result = $instance->getServiceModel();

        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentFee_Service', $result);
    }

    public function testSetServiceModel()
    {
        $mockServiceModel = $this->getMockBuilder('Buckaroo_Buckaroo3Extended_Model_PaymentFee_Service')->getMock();
        $instance = $this->_getInstance();

        $setResult = $instance->setServiceModel($mockServiceModel);
        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Helper_Data', $setResult);

        $getResult = $instance->getServiceModel();
        $this->assertInstanceOf('Buckaroo_Buckaroo3Extended_Model_PaymentFee_Service', $getResult);
        $this->assertEquals($mockServiceModel, $getResult);
    }
}
