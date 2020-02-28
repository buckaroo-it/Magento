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

function whenAvailable(name, callback) {
    var interval = 10; // ms
    window.setTimeout(function() {
        if (window[name]) {
            callback(window[name]);
        } else {
            window.setTimeout(arguments.callee, interval);
        }
    }, interval);
}

function checkStepButton(){
    jQuery_1123('#payment-buttons-container button').removeAttr('disabled');
    current = jQuery_1123("input[name='payment[method]']:checked").val();
    giftcard = jQuery_1123("input[name='payment[method]']:checked").attr('data-giftcard');
    if(current=='buckaroo3extended_giftcards' && giftcard){
        if(Math.round(jQuery_1123("#alreadyPaid").val())>0){
            jQuery_1123('#payment-buttons-container button').attr('disabled', true);
        }
    }
}

function checkPayments(){
    console.log('checkPayments', jQuery_1123("#alreadyPaid").val());
    if(Math.round(jQuery_1123("#alreadyPaid").val())>0){
        var p = ["afterpay","afterpay2","afterpay20","klarna","capayableinstallments"];
        p.forEach(function(item) {
            jQuery_1123('#dt_method_buckaroo3extended_'+item+', dd_method_buckaroo3extended_'+item).remove();
        });
    }
}

document.observe(
    'change', function (e) {
        checkStepButton();
    }
);
