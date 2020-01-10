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
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_Capayable_PaymentMethodTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions('checkout');
    }

    /**
     * @return null|TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_PaymentMethod();
            $this->setProperty('_code', 'buckaroo3extended_capayable', $this->_instance);
        }

        return $this->_instance;
    }

    /**
     * @return array
     */
    public function isAvailableProvider()
    {
        return array(
            'same address' => array(
                array(
                    'name' => 'TIG',
                    'street' => 'Kabelweg 37',
                    'zip' => '1014 BA',
                    'country' => 'NL'
                ),
                array(
                    'name' => 'TIG',
                    'street' => 'Kabelweg 37',
                    'zip' => '1014 BA',
                    'country' => 'NL'
                ),
                true
            ),
            'different address' => array(
                array(
                    'name' => 'TIG',
                    'street' => 'Kabelweg 37',
                    'zip' => '1014 BA',
                    'country' => 'NL'
                ),
                array(
                    'name' => 'TIG',
                    'street' => 'Kabelweg 42',
                    'zip' => '1014 BA',
                    'country' => 'NL'
                ),
                false
            )
        );
    }

    /**
     * @param $billingData
     * @param $shippingData
     * @param $expected
     *
     * @dataProvider isAvailableProvider
     */
    public function testIsAvailable($billingData, $shippingData, $expected)
    {
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended_capayable/active', 1);
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended_capayable/allowed_currencies', 'EUR');
        Mage::app()->getStore()->setConfig('payment/buckaroo3extended_capayable/active', 1);
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended/key', 1);
        Mage::app()->getStore()->setConfig('buckaroo/buckaroo3extended/thumbprint', 1);

        $billingAddress = $this->getMockBuilder('Mage_Sales_Model_Quote_Address')
            ->setMethods(array('getName', 'getStreetFull', 'getPostcode', 'getCountry'))
            ->getMock();
        $billingAddress->expects($this->once())->method('getName')->willReturn($billingData['name']);
        $billingAddress->expects($this->once())->method('getStreetFull')->willReturn($billingData['street']);
        $billingAddress->expects($this->once())->method('getPostcode')->willReturn($billingData['zip']);
        $billingAddress->expects($this->once())->method('getCountry')->willReturn($billingData['country']);

        $shippingAddress = $this->getMockBuilder('Mage_Sales_Model_Quote_Address')
            ->setMethods(array('getName', 'getStreetFull', 'getPostcode', 'getCountry'))
            ->getMock();
        $shippingAddress->expects($this->once())->method('getName')->willReturn($shippingData['name']);
        $shippingAddress->expects($this->once())->method('getStreetFull')->willReturn($shippingData['street']);
        $shippingAddress->expects($this->once())->method('getPostcode')->willReturn($shippingData['zip']);
        $shippingAddress->expects($this->once())->method('getCountry')->willReturn($shippingData['country']);

        $quoteMock = $this->getMockBuilder('Mage_Sales_Model_Quote')
            ->setMethods(array('getBaseGrandTotal', 'getBillingAddress', 'getShippingAddress'))
            ->getMock();
        $quoteMock->expects($this->any())->method('getBaseGrandTotal')->willReturn(1);
        $quoteMock->expects($this->once())->method('getBillingAddress')->willReturn($billingAddress);
        $quoteMock->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddress);

        $instance = $this->_getInstance();
        $result = $instance->isAvailable($quoteMock);

        $this->assertEquals($expected, $result);
    }

    public function testGetOrderPlaceRedirectUrl()
    {
        $postArray = array(
            'payment' => array(
                'buckaroo3extended_capayable' => array(
                    'year' => '1970',
                    'month' => '07',
                    'day' => '10'
                )
            ),
            'buckaroo3extended_capayable_BPE_Customergender' => 1,
            'buckaroo3extended_capayable_BPE_OrderAs' => 2,
            'buckaroo3extended_capayable_BPE_CompanyCOCRegistration' => '123456789',
            'buckaroo3extended_capayable_BPE_CompanyName' => 'TIG',
        );

        Mage::app()->getRequest()->setPost($postArray);

        $sessionArray = array(
            'BPE_Customergender' => 1,
            'BPE_Customerbirthdate' => '1970-07-10',
            'BPE_OrderAs' => 2,
            'BPE_CompanyCOCRegistration' => '123456789',
            'BPE_CompanyName' => 'TIG',
        );

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('setData')->with('additionalFields', $sessionArray);

        $instance = $this->_getInstance();
        $functionResult = $instance->getOrderPlaceRedirectUrl();

        $this->assertInternalType('string', $functionResult);
    }

    public function testGetAllowedCurrencies()
    {
        $instance = $this->_getInstance();
        $result = $instance->getAllowedCurrencies();
        $this->assertEquals(array('EUR'), $result);
    }

    public function testGetRejectedMessage()
    {
        $instance = $this->_getInstance();
        $result = $instance->getRejectedMessage(array());
        $this->assertInternalType('string', $result);
        $this->assertContains('https://www.capayable.com/klantenservice', $result);
    }
}
