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
class TIG_Buckaroo3Extended_Model_Response_Payconiq extends TIG_Buckaroo3Extended_Model_Response_Abstract
{
    /**
     * {@inheritdoc}
     */
    public function processResponse()
    {
        if (is_object($this->_response) && isset($this->_response->RequiredAction)) {
            $payUrl =  Mage::getUrl('buckaroo3extended/payconiq/pay', array('_secure' => true));

            $this->_response->RequiredAction->Type = 'Redirect';
            $this->_response->RequiredAction->RedirectURL = $payUrl;
        }

        return parent::processResponse();
    }
}
