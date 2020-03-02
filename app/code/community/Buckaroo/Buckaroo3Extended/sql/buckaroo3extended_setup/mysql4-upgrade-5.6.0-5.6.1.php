<?php 
$installer = $this;

$installer->startSetup();
$conn = $installer->getConnection();

/**
 * Add AlreadyPaid columns to sales/order
 */

$conn->addColumn(
    $installer->getTable('sales/order'),
    'buckaroo_already_paid',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'base_buckaroo_already_paid',
    "decimal(12,4) null"
);
/**
 * Add AlreadyPaid columns to sales/order_invoice
 */

$conn->addColumn(
    $installer->getTable('sales/invoice'),
    'buckaroo_already_paid',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/invoice'),
    'base_buckaroo_already_paid',
    "decimal(12,4) null"
);

/**
 * Add AlreadyPaid columns to sales/quote
 */

$conn->addColumn(
    $installer->getTable('sales/quote'),
    'buckaroo_already_paid',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/quote'),
    'base_buckaroo_already_paid',
    "decimal(12,4) null"
);

/**
 * Add AlreadyPaid columns to sales/quote_address
 */

$conn->addColumn(
    $installer->getTable('sales/quote_address'),
    'buckaroo_already_paid',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/quote_address'),
    'base_buckaroo_already_paid',
    "decimal(12,4) null"
);

/**
 * Add AlreadyPaid columns to sales/order_creditmemo
 */

$conn->addColumn(
    $installer->getTable('sales/creditmemo'),
    'buckaroo_already_paid',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/creditmemo'),
    'base_buckaroo_already_paid',
    "decimal(12,4) null"
);

$installer->endSetup();
