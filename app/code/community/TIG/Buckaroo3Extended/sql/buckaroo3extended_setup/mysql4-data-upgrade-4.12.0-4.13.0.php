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
