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
class TIG_Buckaroo3Extended_Model_Response_Capture extends TIG_Buckaroo3Extended_Model_Response_BackendOrder
{
    protected $_payment;

    /**
     * {@inheritdoc}
     */
    public function __construct($data)
    {
        $this->setPayment($data['payment']);
        parent::__construct($data);
    }

    /**
     * @param $payment
     */
    public function setPayment($payment)
    {
        $this->_payment = $payment;
    }

    /**
     * @return mixed
     */
    public function getPayment()
    {
        return $this->_payment;
    }

    /**
     * {@inheritdoc}
     */
    public function processResponse()
    {
        if ($this->_response === false) {
            $this->_debugEmail .= "An error occurred in building or sending the SOAP request.. \n";
            return $this->_error();
        }

        $this->_debugEmail .= "verifiying authenticity of the response... \n";
        $verified = $this->_verifyResponse();

        if ($verified !== true) {
            $this->_debugEmail .= "The authenticity of the response could NOT be verified. \n";
            return $this->_verifyError();
        }

        $this->_debugEmail .= "Verified as authentic! \n\n";

        if (is_object($this->_response)
            && isset($this->_response->Key))
        {
            $this->_payment->setTransactionId($this->_response->Key);
            $this->_debugEmail .= 'Transaction key saved: ' . $this->_response->Key . "\n";
        }

        $parsedResponse = $this->_parseResponse();
        $this->_addSubCodeComment($parsedResponse);

        $this->_debugEmail .= "Parsed response: " . var_export($parsedResponse, true) . "\n";

        $this->_debugEmail .= "Dispatching custom order processing event... \n";
        Mage::dispatchEvent(
            'buckaroo3extended_response_custom_processing',
            array(
                'model'          => $this,
                'order'          => $this->getOrder(),
                'response'       => $parsedResponse,
                'responseobject' => $this->_response,
            )
        );

        return $this->_requiredAction($parsedResponse);
    }

    /**
     * {@inheritdoc}
     */
    protected function _success($status = self::BUCKAROO_SUCCESS)
    {
        $this->_debugEmail .= "The request was successful \n";
        if(!$this->_order->getEmailSent())
        {
            $this->_order->sendNewOrderEmail();
        }

        $this->sendDebugEmail();
    }

    /**
     * {@inheritdoc}
     */
    protected function _neutral()
    {
        $this->_debugEmail .= "The request was neutral \n";

        $this->sendDebugEmail();
    }
}
