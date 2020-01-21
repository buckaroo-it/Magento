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

$installer->run(
    "CREATE TABLE IF NOT EXISTS `{$installer->getTable('buckaroo3extended/giftcard')}` (
      `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Entity Id',
      `servicecode` varchar(255) NOT NULL COMMENT 'Servicecode',
      `label` varchar(255) NOT NULL COMMENT 'Label',
      PRIMARY KEY (`entity_id`),
      UNIQUE KEY `UNQ_BUCKAROO_GIFTCARD_SERVICECODE` (`servicecode`)
    ) 
    ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Buckaroo Giftcard';"
);

$installer->endSetup();
