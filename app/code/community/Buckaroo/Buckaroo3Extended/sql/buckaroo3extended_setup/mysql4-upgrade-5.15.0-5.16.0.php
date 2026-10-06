<?php
/**
 * Stores the uploaded certificates (and their private keys) encrypted. Values that are already encrypted are
 * left alone.
 */
$installer = $this;

$installer->startSetup();
$conn  = $installer->getConnection();
$table = $installer->getTable('buckaroo_certificates');

if ($conn->isTableExists($table)) {
    $prefix = Buckaroo_Buckaroo3Extended_Model_Certificate::ENCRYPTED_PREFIX;
    $rows   = $conn->fetchAll("SELECT certificate_id, certificate FROM {$table}");

    foreach ($rows as $row) {
        if ($row['certificate'] === '' || strpos($row['certificate'], $prefix) === 0) {
            continue;
        }

        $conn->update(
            $table,
            array('certificate' => $prefix . Mage::helper('core')->encrypt($row['certificate'])),
            array('certificate_id = ?' => $row['certificate_id'])
        );
    }
}

$installer->endSetup();
