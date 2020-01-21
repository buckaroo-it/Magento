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
class Buckaroo_Buckaroo3Extended_Test_Unit_Helper_StreetFormatterTest extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Helper_StreetFormatter */
    protected $_instance = null;

    /**
     * @return Buckaroo_Buckaroo3Extended_Helper_StreetFormatter
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new Buckaroo_Buckaroo3Extended_Helper_StreetFormatter();
        }

        return $this->_instance;
    }

    /**
     * @return array
     */
    public function formatProvider()
    {
        return array(
            'string, only street' => array(
                'Kabelweg',
                array(
                    'street' => 'Kabelweg',
                    'house_number' => '',
                    'number_addition' => ''
                )
            ),
            'string, with housenumber' => array(
                'Kabelweg 37',
                array(
                    'street' => 'Kabelweg',
                    'house_number' => '37',
                    'number_addition' => ''
                )
            ),
            'string, with number addition' => array(
                'Kabelweg 37 A',
                array(
                    'street' => 'Kabelweg',
                    'house_number' => '37',
                    'number_addition' => 'A'
                )
            ),
            'array, only street' => array(
                ['Kabelweg'],
                array(
                    'street' => 'Kabelweg',
                    'house_number' => '',
                    'number_addition' => ''
                )
            ),
            'array, with housenumber' => array(
                ['Kabelweg', '37'],
                array(
                    'street' => 'Kabelweg',
                    'house_number' => '37',
                    'number_addition' => ''
                )
            ),
            'array, with number addition' => array(
                ['Kabelweg', '37', 'A'],
                array(
                    'street' => 'Kabelweg',
                    'house_number' => '37',
                    'number_addition' => 'A'
                )
            ),
        );
    }

    /**
     * @param $streetData
     * @param $expected
     *
     * @dataProvider formatProvider
     */
    public function testFormat($streetData, $expected)
    {
        $instance = $this->_getInstance();
        $result = $instance->format($streetData);

        $this->assertEquals($expected, $result);
    }
}