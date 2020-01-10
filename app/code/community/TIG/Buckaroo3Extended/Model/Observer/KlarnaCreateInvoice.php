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
class TIG_Buckaroo3Extended_Model_Observer_KlarnaCreateInvoice extends Mage_Core_Model_Abstract
{
    const XPATH_BUCKAROO_KLARNA_INVOICE_WHEN_SHIPPING = 'buckaroo/buckaroo3extended_klarna/auto_invoice_when_shipping';
    const XPATH_BUCKAROO_KLARNA_INVOICE_CAPTURE_TYPE  = 'buckaroo/buckaroo3extended_klarna/auto_invoice_capture_mode';

    /**
     * This observer is triggerd by: sales_order_shipment_save_after
     * When the payment is Klarna and the option auto_invoice_when_shipping is enabled in the advanced settings
     * of the Klarna configuration, the invoice will automaticly be created.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        /** @noinspection PhpUndefinedMethodInspection */
        $shipment = $observer->getShipment();
        if (!$this->_isActiveAfterShipment($shipment->getStoreId())) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $shipment->getOrder();
        if (!$this->_isKlarnaPayment($order)) {
            return $this;
        }

        if (!$order->canInvoice()) {
            Mage::throwException(Mage::helper('core')->__('Cannot create an invoice'));
        }

        $qtys = $this->_getQtysShipped($shipment, $order->getAllItems());

        /** When qtys is empty throw exception, otherwhise the whole order will be invoiced
         *  and that should only happen when all the shipped items are included within the order_item_collection.
         */
        if (empty($qtys)) {
            Mage::throwException(
                Mage::helper('core')->__(
                    'Cannot create an invoice, because Klarna can not work with an empty qtys array'
                )
            );
        }

        $this->_createInvoice($order, $qtys);

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $qtys
     */
    protected function _createInvoice(Mage_Sales_Model_Order $order, $qtys)
    {
        try {
            /** @var Mage_Sales_Model_Service_Order $service */
            $service = Mage::getModel('sales/service_order', $order);
            $invoice = $service->prepareInvoice($qtys);
            if (!$invoice->getTotalQty()) {
                Mage::throwException(
                    Mage::helper('core')->__(
                        'Could not create a invoice for this shipment without products.'
                    )
                );
            }

            /** @noinspection PhpUndefinedMethodInspection */
            $invoice->setRequestedCaptureCase($this->_getCaptureType($order->getStoreId()));
            $invoice->register();

            /** @var Mage_Core_Model_Resource_Transaction $transaction */
            $transaction = Mage::getModel('core/resource_transaction');
            $transaction->addObject($invoice);
            $transaction->addObject($invoice->getOrder());
            $transaction->save();
        } catch (Mage_Core_Exception $exception) {
            Mage::throwException($exception->getMessage());
        }
    }

    /**
     * The qtys used for invoicing needs to contain the item_id's from the order item collection.
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param Mage_Sales_Model_Resource_Order_Item_Collection $orderItems
     *
     * @return mixed
     */
    protected function _getQtysShipped($shipment, $orderItems)
    {
        $shippedItems = array();
        /** @var Mage_Sales_Model_Order_Shipment_Item $item */
        foreach ($shipment->getAllItems() as $item) {
            $shippedItems[$item->getSku()] = $item->getQty();
        }

        $qtys = array();
        /** @var Mage_Sales_Model_Order_Item $orderItem */
        foreach ($orderItems as $orderItem) {
            if (!key_exists($orderItem->getSku(), $shippedItems) || !isset($shippedItems[$orderItem->getSku()])) {
                continue;
            }

            $qtys[$orderItem->getId()] = $shippedItems[$orderItem->getSku()];
        }

        return $qtys;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     */
    protected function _isKlarnaPayment(Mage_Sales_Model_Order $order)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        /** The first characters are "buckaroo3extended_" which are the same for all methods.
        Therefore we don't need to validate this part. */
        $paymentMethodCode = substr($payment->getMethodInstance()->getCode(), 18);

        return $paymentMethodCode == 'klarna';
    }

    /**
     * @param null $storeId
     *
     * @return string
     */
    protected function _getCaptureType($storeId = null)
    {
        return Mage::getStoreConfig(static::XPATH_BUCKAROO_KLARNA_INVOICE_CAPTURE_TYPE, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    protected function _isActiveAfterShipment($storeId = null)
    {
        return Mage::getStoreConfigFlag(static::XPATH_BUCKAROO_KLARNA_INVOICE_WHEN_SHIPPING, $storeId);
    }
}
