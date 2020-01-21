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

var phoneNumber = false;

document.observe(
    'change', function (e) {
    if (e.findElement('#p_method_buckaroo3extended_afterpay')) {
       phoneNumber = jQuery_1123("#billing\\:telephone").val();

        if (!phoneNumber) {
            jQuery_1123('#buckaroo3extended_afterpay_BPE_Customerphone').parent().parent().show();
        } else {
            jQuery_1123('#buckaroo3extended_afterpay_BPE_Customerphone').parent().parent().hide();
            jQuery_1123('#buckaroo3extended_afterpay_BPE_Customerphone').val(phoneNumber);
        }
    }

    if (e.findElement("#billing\\:telephone")) {
        phoneNumber = jQuery_1123("#billing\\:telephone").val();
        if (!phoneNumber) {
            jQuery_1123('#buckaroo3extended_afterpay_BPE_Customerphone').parent().parent().show();
        } else {
            jQuery_1123('#buckaroo3extended_afterpay_BPE_Customerphone').parent().parent().hide();
            jQuery_1123('#buckaroo3extended_afterpay_BPE_Customerphone').val(phoneNumber);
        }
    }

    jQuery_1123('#buckaroo3extended_afterpay_BPE_Customerphone').change(
        function (e) {
        jQuery_1123("#billing\\:telephone").val(jQuery_1123('#buckaroo3extended_afterpay_BPE_Customerphone').val());
        }
    );

    }
);
