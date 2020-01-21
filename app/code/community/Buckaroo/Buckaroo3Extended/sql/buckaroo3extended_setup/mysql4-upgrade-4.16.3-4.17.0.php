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

/** @var Buckaroo_Buckaroo3Extended_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();
$conn = $installer->getConnection();
$tableName = $installer->getTable('sales/order');

if (!$conn->tableColumnExists($tableName, 'buckaroo_reservation_number')) {
    $conn->addColumn(
        $tableName,
        'buckaroo_reservation_number',
        'varchar(255) null'
    );
}

$installer->endSetup();
