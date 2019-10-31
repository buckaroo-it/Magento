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
class TIG_Buckaroo3Extended_Test_Unit_Model_Sources_Payconiq_AvailableCurrenciesTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Model_Sources_Payconiq_AvailableCurrencies */
    protected $_instance = null;

    /**
     * @return null|TIG_Buckaroo3Extended_Model_Sources_Payconiq_AvailableCurrencies
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_Sources_Payconiq_AvailableCurrencies();
        }

        return $this->_instance;
    }

    public function testToOptionArray()
    {
        $expectedOptions = array(
            'AUD', 'BRL', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'JPY',
            'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'USD'
        );

        $instance = $this->_getInstance();
        $result = $instance->toOptionArray();

        $this->assertInternalType('array', $result);

        foreach ($result as $currency) {
            $this->assertContains($currency['value'], $expectedOptions);
        }
    }
}
