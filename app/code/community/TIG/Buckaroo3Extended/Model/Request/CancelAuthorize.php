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
class TIG_Buckaroo3Extended_Model_Request_CancelAuthorize extends TIG_Buckaroo3Extended_Model_Request_Abstract
{
    protected $_payment;

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
     * @param array $params
     */
    public function __construct($params = array())
    {
        $this->setPayment($params['payment']);
        $this->setOrder($params['payment']->getOrder());

        parent::__construct();

        // make the response use quote as order
        $this->setResponseModelClass('buckaroo3extended/response_cancelAuthorize');
    }

    /**
     * The responsemodel in the catch needs extra data that the abstract doesn't provide
     *
     * {@inheritdoc}
     */
    public function sendRequest()
    {
        try {
            return $this->_sendRequest();
        } catch (Exception $e) {
            Mage::helper('buckaroo3extended')->logException($e);
            $responseModel = Mage::getModel(
                $this->_responseModelClass, array(
                'response'   => false,
                'XML'        => false,
                'debugEmail' => $this->_debugEmail,
                'payment'    => $this->_payment
                )
            );
            return $responseModel->setOrder($this->_order)
                ->processResponse();
        }
    }

    /**
     * @return mixed
     */
    protected function _sendRequest()
    {
        Mage::dispatchEvent('buckaroo3extended_request_setmethod', array('request' => $this, 'order' => $this->_order));

        $this->_debugEmail .= 'Chosen payment method: ' . $this->_method . "\n";

        $this->_debugEmail .= "\n";
        //forms an array with all payment-independant variables (such as merchantkey, order id etc.) which are required for the transaction request
        $this->_addBaseVariables();
        $this->_addOrderVariables();
        $this->_addShopVariables();
        $this->_addSoftwareVariables();
        $this->_addCancelAuthorizeVariables();

        $this->_debugEmail .= "Firing request events. \n";
        //event that allows individual payment methods to add additional variables such as bankaccount number
        Mage::dispatchEvent('buckaroo3extended_cancelauthorize_request_addservices', array('request' => $this, 'order' => $this->_order));
        Mage::dispatchEvent('buckaroo3extended_cancelauthorize_request_addcustomvars', array('request' => $this, 'order' => $this->_order));
        Mage::dispatchEvent('buckaroo3extended_cancelauthorize_request_addcustomparameters', array('request' => $this, 'order' => $this->_order));

        $this->_debugEmail .= "Events fired! \n";

        //clean the array for a soap request
        $this->setVars($this->_cleanArrayForSoap($this->getVars()));

        $this->_debugEmail .= "Variable array:" . var_export($this->_vars, true) . "\n\n";
        $this->_debugEmail .= "Building SOAP request... \n";

        //send the transaction request using SOAP
        /** @var TIG_Buckaroo3Extended_Model_Soap $soap */
        $soap = Mage::getModel('buckaroo3extended/soap', array('vars' => $this->getVars(), 'method' => $this->getMethod()));
        list($response, $responseXML, $requestXML) = $soap->transactionRequest();


        $this->_debugEmail .= "The SOAP request has been sent. \n";

        if (!is_object($requestXML) || !is_object($responseXML)) {
            $this->_debugEmail .= "Request or response was not an object \n";
        } else {
            $this->_debugEmail .= "Request: " . var_export($requestXML->saveXML(), true) . "\n";
            $this->_debugEmail .= "Response: " . var_export($response, true) . "\n";
            $this->_debugEmail .= "Response XML:" . var_export($responseXML->saveXML(), true) . "\n\n";
        }

        $this->_debugEmail .= "Processing response... \n";

        //process the response
        $responseModel = Mage::getModel(
            $this->_responseModelClass, array(
            'response'   => $response,
            'XML'        => $responseXML,
            'debugEmail' => $this->_debugEmail,
            'payment'    => $this->_payment,
            )
        );

        if (!$responseModel->getOrder()) {
            $responseModel->setOrder($this->_order);
        }

        return $responseModel->processResponse();
    }
}
