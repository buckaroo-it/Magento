<?php
$installer = $this;

$installer->startSetup();
$status = array(
        'status' => 'buckaroo_giftcard',
        'label' => 'Buckaroo (giftcard)',
        'is_new' => 1,
        'form_key' => '',
        'store_labels' => array(),
        'state' => 'new'
);

$stat = Mage::getModel('sales/order_status')->load('buckaroo_giftcard');

/* Add Status */
if ($stat->getStatus()) {
    return;
}

$stat->setData($status)->setStatus('buckaroo_giftcard');

try {
    $stat->save();
} catch (Mage_Core_Exception $e) {
    throw $e;
}

/* Assign Status to State */
if ($stat && $stat->getStatus()) {
    try {
        $stat->assignState($status['state'], false);
    } catch (Mage_Core_Exception $e) {
        throw $e;
    }
    catch (Exception $e) {
        throw $e;
    }
}

$installer->endSetup();
