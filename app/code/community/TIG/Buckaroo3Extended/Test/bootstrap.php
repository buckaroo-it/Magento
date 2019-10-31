<?php

if (strpos(__DIR__, '.modman') !== false) {
    // @codingStandardsIgnoreLine
    require_once(dirname(__DIR__) . '/../../../../../../../app/Mage.php');
} else {
    // @codingStandardsIgnoreLine
    require_once(__DIR__ . '/../../../../../../Mage.php');
}

// @codingStandardsIgnoreLine
ini_set('display_errors', true);
error_reporting(-1);
TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase::resetMagento();
