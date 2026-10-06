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

/**
 * iDEAL is now iDEAL | Wero. Titles that still have the old default are renamed; a title that the merchant changed
 * is left as it is.
 */
$configTable = $installer->getTable('core_config_data');
$titles = array(
    'buckaroo3extended_ideal'           => array('iDEAL', 'iDeal', 'iDEAL | Wero'),
    'buckaroo3extended_idealprocessing' => array('iDEAL Processing', 'iDeal Processing', 'iDEAL | Wero Processing'),
);

foreach ($titles as $code => $names) {
    $new = array_pop($names);

    foreach (array('buckaroo', 'payment') as $section) {
        $conn->update(
            $configTable,
            array('value' => $new),
            array('path = ?' => $section . '/' . $code . '/title', 'value IN (?)' => $names)
        );
    }
}

$installer->endSetup();
