<?php 
$installer = $this;

$installer->startSetup();
$conn = $installer->getConnection();

$salesFlatOrderTableName = $installer->getTable('sales_flat_order');
$salesOrderTableName = $installer->getTable('sales_order');

try {
    if (!$conn->tableColumnExists($salesFlatOrderTableName, 'transaction_key')) {
        $installer->run("ALTER TABLE `{$salesFlatOrderTableName}` ADD `transaction_key` varchar(50) NULL");
    }
} catch (Exception $e) {
    try {
        if (!$conn->tableColumnExists($salesOrderTableName, 'transaction_key')) {
            $installer->run("ALTER TABLE `{$salesOrderTableName}` ADD `transaction_key` varchar(50) NULL");
        }
    } catch (Exception $e) {
        throw $e;
    }
}

$installer->endSetup();
