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
class TIG_Buckaroo3Extended_Test_Unit_Block_PaymentMethods_Dankort_Checkout_FormTest
    extends TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase
{
    /** @var null|TIG_Buckaroo3Extended_Block_PaymentMethods_Dankort_Checkout_Form */
    protected $_instance = null;

    public function setUp()
    {
        $this->registerMockSessions('checkout');

        $session = Mage::getSingleton('checkout/session');
        $session->expects($this->exactly(2))->method('getQuote')->willReturnSelf();
    }

    /**
     * @return TIG_Buckaroo3Extended_Block_PaymentMethods_Dankort_Checkout_Form
     */
    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = new TIG_Buckaroo3Extended_Block_PaymentMethods_Dankort_Checkout_Form();
        }

        return $this->_instance;
    }

    public function testGetTemplate()
    {
        $instance = $this->_getInstance();
        $template = $instance->getTemplate();
        $this->assertEquals('buckaroo3extended/dankort/checkout/form.phtml', $template);
    }
}
