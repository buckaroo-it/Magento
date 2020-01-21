<?php
$installer = $this;

$installer->startSetup();
$conn = $installer->getConnection();
$tableName = $installer->getTable('sales/order');

if (!$conn->tableColumnExists($tableName, 'buckaroo_service_version_used')) {
    $conn->addColumn($tableName, 'buckaroo_service_version_used', 'smallint(5) null');
}
    
$installer->endSetup();
