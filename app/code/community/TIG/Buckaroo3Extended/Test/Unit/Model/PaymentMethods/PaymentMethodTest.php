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
class TIG_Buckaroo3Extended_Test_Unit_Model_PaymentMethods_PaymentMethodTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    protected $_code = 'unittest_payment';

    /** @var null|TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod */
    protected $_instance = null;

    public function setUp()
    {
        $configData = $this->testGetConfigDataProvider();

        foreach ($configData as $config) {
            $pathstart = 'payment';

            if ($config[0] == 'payment_action') {
                $pathstart = 'buckaroo';
            }

            Mage::app()->getStore()->setConfig($pathstart . '/' . $this->_code . '/' . $config[0], $config[1]);
        }
    }

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Model_PaymentMethods_PaymentMethod();
            $this->setProperty('_code', $this->_code, $this->_instance);
        }

        return $this->_instance;
    }

    public function testGetConfigDataProvider()
    {
        return array(
            array(
                'active',
                '1'
            ),
            array(
                'payment_action',
                'order'
            ),
            array(
                'title',
                'Payment Title'
            ),
            array(
                'sort_order',
                '10'
            )
        );
    }

    /**
     * @param $field
     * @param $expected
     *
     * @dataProvider testGetConfigDataProvider
     */
    public function testGetConfigData($field, $expected)
    {
        $instance = $this->_getInstance();
        $result = $instance->getConfigData($field);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function hideForPosPaymentProvider()
    {
        return array(
            'no cookie, not pospayment' => array(
                null,
                'buckaroo3extended_tig',
                false
            ),
            'with cookie, not pospayment' => array(
                '1234567',
                'buckaroo3extended_tig',
                true
            ),
            'no cookie, is pospayment' => array(
                null,
                'buckaroo3extended_pospayment',
                false
            ),
            'with cookie, is pospayment' => array(
                '8901234',
                'buckaroo3extended_pospayment',
                false
            )
        );
    }

    /**
     * @param $terminalid
     * @param $methodCode
     * @param $expected
     *
     * @dataProvider hideForPosPaymentProvider
     */
    public function testHideForPosPayment($terminalid, $methodCode, $expected)
    {
        // @codingStandardsIgnoreLine
        $_COOKIE['Pos-Terminal-Id'] = $terminalid;

        $instance = $this->_getInstance();
        $this->setProperty('_code', $methodCode, $instance);

        $result = $instance->hideForPosPayment();
        $this->assertEquals($expected, $result);
    }

    public function getPosPaymentTerminalIdProvider()
    {
        return array(
            'no cookie or header' => array(
                null,
                null,
                null
            ),
            'with cookie, no header' => array(
                '1234',
                null,
                '1234'
            ),
            'no cookie, with header' => array(
                null,
                '5678',
                '5678'
            ),
            'with both cookie and header' => array(
                '9012',
                '3456',
                '9012'
            ),
        );
    }

    /**
     * @param $terminalidCookie
     * @param $terminalidHeader
     * @param $expected
     *
     * @dataProvider getPosPaymentTerminalIdProvider
     */
    public function testGetPosPaymentTerminalId($terminalidCookie, $terminalidHeader, $expected)
    {
        // @codingStandardsIgnoreLine
        $_COOKIE['Pos-Terminal-Id'] = $terminalidCookie;
        // @codingStandardsIgnoreLine
        $_SERVER['HTTP_POS_TERMINAL_ID'] = $terminalidHeader;

        $instance = $this->_getInstance();
        $result = $instance->getPosPaymentTerminalId();

        $this->assertEquals($expected, $result);
    }

    public function testGetRejectedMessage()
    {
        $instance = $this->_getInstance();
        $result = $instance->getRejectedMessage(array());
        $this->assertFalse($result);
    }
}
