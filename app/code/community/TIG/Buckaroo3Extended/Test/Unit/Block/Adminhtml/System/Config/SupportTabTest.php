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
use TIG_Buckaroo3Extended_Block_Adminhtml_System_Config_SupportTab as SupportTab;

class TIG_Buckaroo3Extended_Test_Unit_Block_Adminhtml_System_Config_SupportTabTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /**
     * @return TIG_Buckaroo3Extended_Block_Adminhtml_System_Config_SupportTab
     */
    public function _getInstance()
    {
        return new SupportTab;
    }

    public function testGetVersion()
    {
        $configValue = Mage::getConfig()->getModuleConfig('TIG_Buckaroo3Extended');
        $expected = (string)$configValue->version;

        $this->assertEquals($expected, $this->_getInstance()->getVersion());
    }

    public function getStabilityProvider()
    {
        return array(
            array('stable', null),
            array('beta', 'beta'),
            array('alpha', 'alpha'),
        );
    }

    /**
     * @dataProvider getStabilityProvider
     */
    public function testGetStability($version, $expected)
    {
        Mage::getConfig()->setNode(SupportTab::XPATH_TIG_BUCKAROO_STABILITY, $version);

        $this->assertEquals($expected, $this->_getInstance()->getStability());
    }
}
