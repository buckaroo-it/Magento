/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
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
