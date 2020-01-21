<?php
$installer = $this;

$installer->startSetup();
$conn = $installer->getConnection();

$salesFlatCreditMemoTable = $installer->getTable('sales_flat_creditmemo');
$salesFlatOrderTable = $installer->getTable('sales_flat_order');
$certificatesTable = $installer->getTable('buckaroo_certificates');

try {
    if (!$conn->tableColumnExists($salesFlatCreditMemoTable, 'transaction_key')) {
        $installer->run("ALTER TABLE `{$salesFlatCreditMemoTable}` ADD `transaction_key` varchar(50) NULL");
    }

    if (!$conn->tableColumnExists($salesFlatOrderTable, 'payment_method_used_for_transaction')) {
        $installer->run(
            "ALTER TABLE `{$salesFlatOrderTable}` ADD `payment_method_used_for_transaction` varchar(50) NULL"
        );
    }

    if (!$conn->tableColumnExists($salesFlatOrderTable, 'currency_code_used_for_transaction')) {
        $installer->run(
            "ALTER TABLE `{$salesFlatOrderTable}` ADD `currency_code_used_for_transaction` varchar(3) NULL"
        );
    }

    $installer->run(
        "CREATE TABLE IF NOT EXISTS `{$certificatesTable}`
        (
            `certificate_id` INT(7) NOT NULL auto_increment,
            `certificate` TEXT NOT NULL,
            `certificate_name` VARCHAR(15) NOT NULL,
            `upload_date` DATETIME NOT NULL,
            PRIMARY KEY  (`certificate_id`),
            UNIQUE (`certificate_name`)
        )"
    );
} catch (Exception $e) {
    throw $e;
}

$installer->endSetup();
