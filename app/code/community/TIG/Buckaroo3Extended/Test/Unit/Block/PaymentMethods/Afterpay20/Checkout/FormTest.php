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
class TIG_Buckaroo3Extended_Block_PaymentMethods_Afterpay20_Checkout_FormTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Block_PaymentMethods_Afterpay20_Checkout_Form */
    protected $_instance = null;

    /** @var Mage_Sales_Model_Quote|PHPUnit_Framework_MockObject_MockObject */
    protected $_quoteMock;

    public function setUp()
    {
        $this->registerMockSessions('checkout');
        $this->registerMockSessions('customer');

        $this->_quoteMock = $this->getMockBuilder('Mage_Sales_Model_Quote')->disableOriginalConstructor()->getMock();

        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->method('getQuote')->willReturn($this->_quoteMock);
    }

    /**
     * @return TIG_Buckaroo3Extended_Block_PaymentMethods_Afterpay20_Checkout_Form
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Block_PaymentMethods_Afterpay20_Checkout_Form();
        }

        return $this->_instance;
    }

    /**
     * @return array
     */
    public function getAcceptanceUrlProvider()
    {
        return array(
            'no country, with method param' => array(
                null,
                'fr_fr',
                'https://documents.myafterpay.com/consumer-terms-conditions/fr_fr/'
            ),
            'with country in known list, no method param' => array(
                'DE',
                '',
                'https://documents.myafterpay.com/consumer-terms-conditions/de_de/'
            ),
            'with country not in known list, no method param' => array(
                'RU',
                '',
                'https://documents.myafterpay.com/consumer-terms-conditions/en_nl/'
            ),
            'no country, no method param' => array(
                null,
                '',
                'https://documents.myafterpay.com/consumer-terms-conditions/en_nl/'
            ),
            'with country in known list, with method param' => array(
                'NL',
                'ru_ru',
                'https://documents.myafterpay.com/consumer-terms-conditions/ru_ru/'
            ),
            'with country not in known list, with method param' => array(
                'ES',
                'en_us',
                'https://documents.myafterpay.com/consumer-terms-conditions/en_us/'
            ),
        );
    }

    /**
     * @param $country
     * @param $methodParam
     * @param $expected
     *
     * @dataProvider getAcceptanceUrlProvider
     */
    public function testGetAcceptanceUrl($country, $methodParam, $expected)
    {
        $addressMock = $this->getMockBuilder('Mage_Sales_Model_Quote_Address')
            ->disableOriginalConstructor()
            ->setMethods(array('getCountry'))
            ->getMock();
        $addressMock->method('getCountry')->willReturn($country);

        $this->_quoteMock->method('getBillingAddress')->willReturn($addressMock);

        $instance = $this->_getInstance();
        $result = $instance->getAcceptanceUrl($methodParam);

        $this->assertEquals($expected, $result);
    }
}
