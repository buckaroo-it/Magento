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
class TIG_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_Form
    extends TIG_Buckaroo3Extended_Block_PaymentMethods_Checkout_Form_Abstract
{
    /**
     * TIG_Buckaroo3Extended_Block_PaymentMethods_Capayable_Checkout_Form constructor.
     */
    public function __construct()
    {
        $this->setTemplate('buckaroo3extended/capayable/checkout/form.phtml');
        parent::_construct();
    }

    /**
     * @return string|int
     */
    public function getOrderAs()
    {
        return $this->getSession()->getData($this->getMethodCode() . '_BPE_OrderAs');
    }

    /**
     * @return string|int
     */
    public function getCompanyCOCRegistration()
    {
        return $this->getSession()->getData($this->getMethodCode() . '_BPE_CompanyCOCRegistration');
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->getSession()->getData($this->getMethodCode() . '_BPE_CompanyName');
    }
}
