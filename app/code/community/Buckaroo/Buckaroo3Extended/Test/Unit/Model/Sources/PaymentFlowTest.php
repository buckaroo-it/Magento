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
class Buckaroo_Buckaroo3Extended_Test_Unit_Model_Sources_PaymentFlowTest
    extends Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_TestCase
{
    /** @var null|Buckaroo_Buckaroo3Extended_Model_Sources_PaymentFlow */
    protected $_instance = null;

    protected function _getInstance()
    {
        if ($this->_instance === null) {
            $this->_instance = Mage::getModel('buckaroo3extended/sources_paymentFlow');
        }

        return $this->_instance;
    }

    /**
     * @return array
     */
    public function testToOptionArrayProvider()
    {
        return array(
            array(Mage_Payment_Model_Method_Abstract::ACTION_ORDER),
            array(Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE)
        );
    }

    /**
     * @param $paymentAction
     *
     * @dataProvider testToOptionArrayProvider
     */
    public function testToOptionArray($paymentAction)
    {
        $instance = $this->_getInstance();
        $options = $instance->toOptionArray();

        $hasOption = false;

        if (is_array($options)) {
            foreach ($options as $option) {
                if ($option['value'] == $paymentAction) {
                    $hasOption = true;
                    break;
                }
            }
        }

        $this->assertTrue($hasOption);
    }
}
