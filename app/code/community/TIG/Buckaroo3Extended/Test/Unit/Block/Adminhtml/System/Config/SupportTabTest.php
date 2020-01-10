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
