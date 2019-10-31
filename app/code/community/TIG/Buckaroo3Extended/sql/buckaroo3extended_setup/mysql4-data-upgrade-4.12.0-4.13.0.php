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
$installer = $this;

$installer->startSetup();

$statusArray = array(
    array(
        'status' => 'buckaroo_pending_payment',
        'label' => 'Buckaroo (waiting for payment)',
        'is_new' => 1,
        'form_key' => '',
        'store_labels' => array(),
        'state' => 'new'
    ),
    array(
        'status' => 'buckaroo_incorrect_payment',
        'label' => 'Buckaroo On Hold (incorrect amount transfered)',
        'is_new' => 1,
        'form_key' => '',
        'store_labels' => array(),
        'state' => 'holded'
    )
);

foreach ($statusArray as $data) {
    // Get the entity from the database
    // @codingStandardsIgnoreLine
    $statusDb = Mage::getModel('sales/order_status')->load($data['status']);

    // Check if it already has a status - if it doesn't, we're going to add it
    if (!$statusDb->getStatus()) {
        $statusDb->setData($data)->setStatus($data['status']);
        // @codingStandardsIgnoreLine
        $statusDb->save();
        $statusDb->assignState($data['state'], false);
    }
}

$installer->endSetup();
