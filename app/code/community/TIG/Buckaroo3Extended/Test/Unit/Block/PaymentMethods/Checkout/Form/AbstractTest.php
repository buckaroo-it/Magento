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
class TIG_Buckaroo3Extended_Test_Unit_Block_PaymentMethods_Checkout_Form_AbstractTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions('checkout');
    }

    /**
     * @return TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract();
        }

        return $this->_instance;
    }

    protected function setMethodCode()
    {
        $methodMock = $this->getMockForAbstractClass(
            'Mage_Payment_Model_Method_Abstract',
            array(),
            '',
            true,
            true,
            true,
            array('getCode')
        );

        $methodMock->method('getCode')->willReturn('tig_method');

        $instance = $this->_getInstance();
        $instance->setData('method', $methodMock);
    }

    /**
     * @return array
     */
    public function getGenderProvider()
    {
        return array(
            'no gender' => array(
                null,
                null,
                null,
                null
            ),
            'session gender' => array(
                '1',
                '2',
                '2',
                '1'
            ),
            'customer gender' => array(
                null,
                '1',
                '2',
                '1'
            ),
            'quote gender' => array(
                null,
                null,
                '2',
                '2'
            )
        );
    }

    /**
     * @param $sessionGender
     * @param $customerGender
     * @param $quoteGender
     * @param $expected
     *
     * @dataProvider getGenderProvider
     */
    public function testGetGender($sessionGender, $customerGender, $quoteGender, $expected)
    {
        $quoteMock = $this->getMockBuilder('Mage_Sales_Model_Quote')->setMethods(array('getCustomerGender'))->getMock();
        $quoteMock->expects($this->exactly((int)(!$sessionGender && !$customerGender)))
            ->method('getCustomerGender')
            ->willReturn($quoteGender);

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('getQuote')->willReturn($quoteMock);
        $checkoutSession->expects($this->once())
            ->method('getData')
            ->with('tig_method_BPE_Customergender')
            ->willReturn($sessionGender);

        $instance = $this->_getInstance();

        $this->setMethodCode();

        $customerMock = $this->getMockBuilder('Mage_Customer_Model_Customer')
            ->setMethods(array('getGender'))
            ->getMock();
        $customerMock->expects($this->exactly((int)!$sessionGender))
            ->method('getGender')
            ->willReturn($customerGender);
        $instance->setCustomer($customerMock);

        $result = $instance->getGender();
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDobProvider()
    {
        return array(
            'session dob' => array(
                array(
                    'day' => '10',
                    'month' => '11',
                    'year' => '1990'
                ),
                '09-12-1991',
                '05-08-1992',
                '1990-11-10'
            ),
            'customer dob' => array(
                array(
                    'day' => null,
                    'month' => null,
                    'year' => null
                ),
                '09-12-1991',
                '05-08-1992',
                '1991-12-09'
            ),
            'quote dob' => array(
                array(
                    'day' => null,
                    'month' => null,
                    'year' => null
                ),
                null,
                '05-08-1992',
                '1992-08-05'
            )
        );
    }

    /**
     * @param $sessionDob
     * @param $customerDob
     * @param $quoteDob
     * @param $expected
     *
     * @dataProvider getDobProvider
     */
    public function testGetDob($sessionDob, $customerDob, $quoteDob, $expected)
    {
        $quoteMock = $this->getMockBuilder('Mage_Sales_Model_Quote')->setMethods(array('getCustomerDob'))->getMock();
        $quoteMock->expects($this->exactly((int)(!$sessionDob['day'] && !$customerDob)))
            ->method('getCustomerDob')
            ->willReturn($quoteDob);

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('getQuote')->willReturn($quoteMock);
        $checkoutSession->expects($this->exactly(3))
            ->method('getData')
            ->withConsecutive(
                $this->onConsecutiveCalls(
                    'tig_method_customerbirthdate[day]',
                    'tig_method_customerbirthdate[month]',
                    'tig_method_customerbirthdate[year]'
                )
            )
            ->willReturnOnConsecutiveCalls(
                $sessionDob['day'],
                $sessionDob['month'],
                $sessionDob['year']
            );

        $instance = $this->_getInstance();

        $customerMock = $this->getMockBuilder('Mage_Customer_Model_Customer')->setMethods(array('getDob'))->getMock();
        $customerMock->expects($this->exactly((int)!$sessionDob['day']))
            ->method('getDob')
            ->willReturn($customerDob);
        $instance->setCustomer($customerMock);

        $this->setMethodCode();

        $result = $instance->getDob();
        $this->assertEquals($expected, $result);
    }
}
