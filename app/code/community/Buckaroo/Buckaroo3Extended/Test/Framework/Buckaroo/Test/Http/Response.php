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
class Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_Http_Response extends Mage_Core_Controller_Response_Http
{
    /**
     * @var bool
     */
    protected $_headersSent = false;

    /**
     * @param boolean $headersSent
     *
     * @return Buckaroo_Test_Http_Response
     */
    public function setHeadersSent($headersSent)
    {
        $this->_headersSent = $headersSent;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getHeadersSent()
    {
        return $this->_headersSent;
    }

    /**
     * @param bool $throw
     *
     * @return bool
     */
    // @codingStandardsIgnoreLine
    public function canSendHeaders($throw = false)
    {
        $canSendHeaders = !$this->getHeadersSent();
        return $canSendHeaders;
    }

    /**
     * @return Mage_Core_Controller_Response_Http
     */
    public function sendHeaders()
    {
        $this->setHeadersSent(true);

        return $this;
    }

    /**
     * @return $this
     */
    public function sendResponse()
    {
        return $this;
    }

}
