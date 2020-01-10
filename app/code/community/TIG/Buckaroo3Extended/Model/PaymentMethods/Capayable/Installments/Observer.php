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
class TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Installments_Observer
    extends TIG_Buckaroo3Extended_Model_PaymentMethods_Capayable_Observer
{
    protected $_code = 'buckaroo3extended_capayableinstallments';

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_request_addservices(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();
        $vars = $request->getVars();
        $serviceVersion = $this->_getServiceVersion();

        $array = array($this->_method => array('action'  => 'PayInInstallments', 'version' => $serviceVersion));

        if (array_key_exists('services', $vars) && is_array($vars['services'])) {
            $vars['services'] = array_merge($vars['services'], $array);
        } else {
            $vars['services'] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    /**
     * PayInInstallments doesn't support orderId, so remove it from the vars after adding the necessary data
     *
     * {@inheritdoc}
     */
    public function buckaroo3extended_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        parent::buckaroo3extended_request_addcustomvars($observer);

        $request = $observer->getRequest();
        $vars    = $request->getVars();
        $vars    = $this->_addGuaranteeVersion($vars);

        // PayInInstallments doesn't support orderId, but does require an invoiceId
        $vars['invoiceId'] = $vars['orderId'];
        unset($vars['orderId']);

        $request->setVars($vars);
    }

    /**
     * Added type of Installment, setting by config
     * @param $vars
     * @return array
     */
    protected function _addGuaranteeVersion($vars)
    {
        $storeId = Mage::app()->getStore()->getStoreId();
        $versionSetting = Mage::getStoreConfig('buckaroo/' . $this->_code . '/version', $storeId);

        $array = array(
            'IsInThreeGuarantee' => ($versionSetting) ? 'true' : 'false',
        );

        $vars['customVars'][$this->_method] = array_merge($vars['customVars'][$this->_method], $array);

        return array_merge($vars, $array);

    }
}
