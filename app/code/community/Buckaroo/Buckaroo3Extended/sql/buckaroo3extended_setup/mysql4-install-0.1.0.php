<?php
$installer = $this;

$installer->startSetup();

$versionFive = '1.5.0.0';
$versionNine = '1.9.0.0';
$versionTen = '1.10.0.0';
$isVersionFive = version_compare(Mage::getVersion(), $versionFive, '<') ? false : true;
$isVersionNine = version_compare(Mage::getVersion(), $versionNine, '<') ? false : true;
$isVersionTen = version_compare(Mage::getVersion(), $versionTen, '<') ? false : true;

if (!$isVersionFive || ($isVersionNine && !$isVersionTen)) {
    return;
}

//define statusses to be added
$statusArray = array(
    'buckaroo_pending_payment' => array(
        'status' => 'buckaroo_pending_payment',
        'label' => 'Buckaroo (waiting for payment)',
        'is_new' => 1,
        'form_key' => '',
        'store_labels' => array(),
        'state' => 'new'
    ),
    'buckaroo_incorrect_amount' => array(
        'status' => 'buckaroo_incorrect_payment',
        'label' => 'Buckaroo On Hold (incorrect amount transfered)',
        'is_new' => 1,
        'form_key' => '',
        'store_labels' => array(),
        'state' => 'holded'
    )
);

//add the statusses and link them to their defined states
foreach ($statusArray as $status) {
    // @codingStandardsIgnoreLine
    $stat = Mage::getModel('sales/order_status')->load($status['status']);

    /* Add Status */
    if ($status['is_new'] && $stat->getStatus()) {
        return;
    }

    $stat->setData($status)->setStatus($status['status']);

    try {
        // @codingStandardsIgnoreLine
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
}

$installer->endSetup();
