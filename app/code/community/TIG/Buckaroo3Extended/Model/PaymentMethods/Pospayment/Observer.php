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
class TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_Observer extends TIG_Buckaroo3Extended_Model_Observer_Abstract
{
    /** @var string */
    protected $_code = 'buckaroo3extended_pospayment';

    /** @var string */
    protected $_method = 'pospayment';

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_request_setmethod(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $request = $observer->getRequest();
        $codeBits = explode('_', $this->_code);

        $code = end($codeBits);
        $request->setMethod($code);

        return $this;
    }

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

        /** @var TIG_Buckaroo3Extended_Model_Request_Abstract $request */
        $request = $observer->getRequest();
        $vars = $request->getVars();

        $array = array(
            $this->_method => array(
                'action' => 'Pay',
                'version' => 2,
            ),
        );

        if (array_key_exists('services', $vars) && is_array($vars['services'])) {
            $vars['services'] = array_merge($vars['services'], $array);
        } else {
            $vars['services'] = $array;
        }

        $request->setVars($vars);

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_request_addcustomvars(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        /** @var TIG_Buckaroo3Extended_Model_Request_Abstract $request */
        $request = $observer->getRequest();
        $vars = $request->getVars();

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        /** @var TIG_Buckaroo3Extended_Model_PaymentMethods_Pospayment_PaymentMethod $methodInstance */
        $methodInstance = $order->getPayment()->getMethodInstance();
        $terminalId = $methodInstance->getPosPaymentTerminalId();

        $vars['customVars'][$this->_method]['TerminalID'] = $terminalId;

        $request->setVars($vars);

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_response_custom_processing(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $returnUrl = Mage::getUrl('buckaroo3extended/checkout/pospaymentPending', array('_secure' => true));

        $responseModel = $observer->getModel();
        $responseModel->sendDebugEmail();

        Mage::app()->getFrontController()->getResponse()->setRedirect($returnUrl)->sendResponse();
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function buckaroo3extended_push_custom_save_invoice_after(Varien_Event_Observer $observer)
    {
        if ($this->_isChosenMethod($observer) === false) {
            return $this;
        }

        $response = $observer->getResponse();

        if ($response['status'] !== self::BUCKAROO_SUCCESS) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        $push = $observer->getPush()->getPostArray();

        $this->saveTicketToInvoice($order, $push);

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array                  $push
     */
    private function saveTicketToInvoice($order, $push)
    {
        if (!isset($push['brq_SERVICE_pospayment_Ticket']) || strlen($push['brq_SERVICE_pospayment_Ticket']) <= 0) {
            return;
        }

        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoiceCollection */
        $invoiceCollection = $order->getInvoiceCollection()
            ->addFieldToFilter('transaction_id', array('eq' => $push['brq_transactions']))
            ->setOrder('entity_id', Mage_Sales_Model_Resource_Order_Invoice_Collection::SORT_ORDER_DESC);

        if ($invoiceCollection->count() < 1) {
            return;
        }

        $ticketDecoded = urldecode($push['brq_SERVICE_pospayment_Ticket']);

        // A line in the ticket may start with a undesirable number between brackets, e.g. [0] or [1]
        $ticketFixed = preg_replace('/^\[[0-9]*\]/m', '', $ticketDecoded);
        $ticketComment = nl2br($ticketFixed);

        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $invoiceCollection->getFirstItem();
        $invoice->addComment($ticketComment, true, true)->save();
    }
}
