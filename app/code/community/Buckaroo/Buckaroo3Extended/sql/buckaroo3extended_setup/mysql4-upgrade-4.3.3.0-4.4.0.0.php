<?php 
$installer = $this;

$installer->startSetup();
$conn = $installer->getConnection();

$conn->changeColumn(
    $this->getTable('buckaroo3extended/certificate'),
    'certificate_name',
    'certificate_name',
    'varchar(255) NOT NULL'
);
    
$conn->addColumn($installer->getTable('sales/order'), 'buckaroo_secure_enrolled', 'smallint(5) null');
    
$conn->addColumn($installer->getTable('sales/order'), 'buckaroo_secure_authenticated', 'smallint(5) null');

/**
 * Add PaymentFee columns to sales/order
 */
$conn->addColumn(
    $installer->getTable('sales/order'),
    'buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'base_buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'buckaroo_fee_invoiced',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'base_buckaroo_fee_invoiced',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'buckaroo_fee_tax',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'base_buckaroo_fee_tax',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'buckaroo_fee_tax_invoiced',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'base_buckaroo_fee_tax_invoiced',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'buckaroo_fee_refunded',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'base_buckaroo_fee_refunded',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'buckaroo_fee_tax_refunded',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/order'),
    'base_buckaroo_fee_tax_refunded',
    "decimal(12,4) null"
);

/**
 * Add PaymentFee columns to sales/order_invoice
 */
$conn->addColumn(
    $installer->getTable('sales/invoice'),
    'buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/invoice'),
    'base_buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/invoice'),
    'buckaroo_fee_tax',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/invoice'),
    'base_buckaroo_fee_tax',
    "decimal(12,4) null"
);

/**
 * Add PaymentFee columns to sales/quote
 */
$conn->addColumn(
    $installer->getTable('sales/quote'),
    'buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/quote'),
    'base_buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/quote'),
    'buckaroo_fee_tax',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/quote'),
    'base_buckaroo_fee_tax',
    "decimal(12,4) null"
);

/**
 * Add PaymentFee columns to sales/quote_address
 */
$conn->addColumn(
    $installer->getTable('sales/quote_address'),
    'buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/quote_address'),
    'base_buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/quote_address'),
    'buckaroo_fee_tax',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/quote_address'),
    'base_buckaroo_fee_tax',
    "decimal(12,4) null"
);

/**
 * Add PaymentFee columns to sales/order_creditmemo
 */
$conn->addColumn(
    $installer->getTable('sales/creditmemo'),
    'buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/creditmemo'),
    'base_buckaroo_fee',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/creditmemo'),
    'buckaroo_fee_tax',
    "decimal(12,4) null"
);
$conn->addColumn(
    $installer->getTable('sales/creditmemo'),
    'base_buckaroo_fee_tax',
    "decimal(12,4) null"
);

$installer->endSetup();
