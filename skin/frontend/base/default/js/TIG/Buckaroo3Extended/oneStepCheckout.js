oldFirstname = '';
oldLastname = '';
oldEmail = '';
oldGender = '';
oldDay = jQuery_1123("#billing\\:day").val();
oldMonth = jQuery_1123("#billing\\:month").val();
oldYear = jQuery_1123("#billing\\:year").val();
oldPhone = '';
originalAddress = jQuery_1123('#billing-address-select option:selected').val();
changedAddress = false;
jQuery_1123("#billing\\:firstname").change(
    function () {
        firstname = jQuery_1123(this).val();

        if (!jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customerfirstname').val()
            || jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customerfirstname').val() == oldFirstname
            || changedAddress
        ) {
            jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customerfirstname').val(firstname);
            sendData(jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customerfirstname'));
        }

        if (!jQuery_1123('#buckaroo3extended_payperemail_BPE_Customerfirstname').val()
            || jQuery_1123('#buckaroo3extended_payperemail_BPE_Customerfirstname').val() == oldFirstname
            || changedAddress
        ) {
            jQuery_1123('#buckaroo3extended_payperemail_BPE_Customerfirstname').val(firstname);
            sendData(jQuery_1123('#buckaroo3extended_payperemail_BPE_Customerfirstname'));
        }

        jQuery_1123('#buckaroo3extended_paymentguarantee_BPE_Customername').val(
            firstname + ' ' + jQuery_1123("#billing\\:lastname").val()
        );

        jQuery_1123('#buckaroo3extended_transfer_BPE_Customername').val(
            firstname + ' ' + jQuery_1123("#billing\\:lastname").val()
        );

        jQuery_1123('#buckaroo3extended_directdebit_account_owner').val(
            firstname + ' ' + jQuery_1123("#billing\\:lastname").val()
        );

        jQuery_1123('#buckaroo3extended_empayment_BPE_Accountholder').val(
            firstname + ' ' + jQuery_1123("#billing\\:lastname").val()
        );

        oldFirstname = firstname;
    }
);
jQuery_1123("#billing\\:lastname").change(
    function () {
        lastname = jQuery_1123(this).val();

        if (!jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customerlastname').val()
            || jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customerlastname').val() == oldLastname
            || changedAddress
        ) {
            jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customerlastname').val(lastname);
            sendData(jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customerlastname'));
        }

        if (!jQuery_1123('#buckaroo3extended_payperemail_BPE_Customerlastname').val()
            || jQuery_1123('#buckaroo3extended_payperemail_BPE_Customerlastname').val() == oldLastname
            || changedAddress
        ) {
            jQuery_1123('#buckaroo3extended_payperemail_BPE_Customerlastname').val(lastname);
            sendData(jQuery_1123('#buckaroo3extended_payperemail_BPE_Customerlastname'));
        }

        jQuery_1123('#buckaroo3extended_paymentguarantee_BPE_Customername').val(
            jQuery_1123("#billing\\:firstname").val() + ' ' + lastname
        );

        jQuery_1123('#buckaroo3extended_transfer_BPE_Customername').val(
            jQuery_1123("#billing\\:firstname").val() + ' ' + lastname
        );

        jQuery_1123('#buckaroo3extended_directdebit_account_owner').val(
            jQuery_1123("#billing\\:firstname").val() + ' ' + lastname
        );

        jQuery_1123('#buckaroo3extended_empayment_BPE_Accountholder').val(
            jQuery_1123("#billing\\:firstname").val() + ' ' + lastname
        );

        oldLastname = lastname;
    }
);
jQuery_1123("#billing\\:email").change(
    function () {
        email = jQuery_1123(this).val();

        if (!jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customermail').val()
            || jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customermail').val() == oldEmail
            || changedAddress
        ) {
            jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customermail').val(email);
            sendData(jQuery_1123('#buckaroo3extended_onlinegiro_BPE_Customermail'));
        }

        if (!jQuery_1123('#buckaroo3extended_transfer_BPE_Customermail').val()
            || jQuery_1123('#buckaroo3extended_transfer_BPE_Customermail').val() == oldEmail
            || changedAddress
        ) {
            jQuery_1123('#buckaroo3extended_transfer_BPE_Customermail').val(email);
            sendData(jQuery_1123('#buckaroo3extended_transfer_BPE_Customermail'));
        }

        if (!jQuery_1123('#buckaroo3extended_payperemail_BPE_Customermail').val()
            || jQuery_1123('#buckaroo3extended_payperemail_BPE_Customermail').val() == oldEmail
            || changedAddress
        ) {
            jQuery_1123('#buckaroo3extended_payperemail_BPE_Customermail').val(email);
            sendData(jQuery_1123('#buckaroo3extended_payperemail_BPE_Customermail'));
        }

        oldEmail = email;
    }
);
jQuery_1123("#billing\\:telephone").change(
    function () {
        phone = jQuery_1123(this).val();

        if (!jQuery_1123('#buckaroo3extended_paymentguarantee_BPE_Customerphone').val()
            || jQuery_1123('#buckaroo3extended_paymentguarantee_BPE_Customerphone').val() == oldPhone
            || changedAddress
        ) {
            jQuery_1123('#buckaroo3extended_paymentguarantee_BPE_Customerphone').val(phone);
            sendData(jQuery_1123('#buckaroo3extended_paymentguarantee_BPE_Customerphone'));
        }

        jQuery_1123('#buckaroo3extended_paymentguarantee_BPE_Customerphone').val(phone);
        oldPhone = phone;
    }
);
jQuery_1123("#billing\\:gender").change(
    function () {
        gender = jQuery_1123("#billing\\:gender option:selected").val();

        if (!jQuery_1123("#buckaroo3extended_paymentguarantee_BPE_Customergender option:selected").val()
            || jQuery_1123("#buckaroo3extended_paymentguarantee_BPE_Customergender option:selected").val() == oldGender
            || changedAddress
        ) {
            jQuery_1123("#buckaroo3extended_paymentguarantee_BPE_Customergender option[value='" + gender + "']").attr('selected', 'selected');
        }

        if (!jQuery_1123("#buckaroo3extended_onlinegiro_BPE_Customergender option:selected").val()
            || jQuery_1123("#buckaroo3extended_onlinegiro_BPE_Customergender option:selected").val() == oldGender
            || changedAddress
        ) {
            jQuery_1123("#buckaroo3extended_onlinegiro_BPE_Customergender option[value='" + gender + "']").attr('selected', 'selected');
        }

        if (!jQuery_1123("#buckaroo3extended_transfer_BPE_Customergender option:selected").val()
            || jQuery_1123("#buckaroo3extended_transfer_BPE_Customergender option:selected").val() == oldGender
            || changedAddress
        ) {
            jQuery_1123("#buckaroo3extended_transfer_BPE_Customergender option[value='" + gender + "']").attr('selected', 'selected');
        }

        if (!jQuery_1123("#buckaroo3extended_payperemail_BPE_Customergender option:selected").val()
            || jQuery_1123("#buckaroo3extended_payperemail_BPE_Customergender option:selected").val() == oldGender
            || changedAddress
        ) {
            jQuery_1123("#buckaroo3extended_payperemail_BPE_Customergender option[value='" + gender + "']").attr('selected', 'selected');
        }

        oldGender = gender;

        saveOscBilling();
    }
);
jQuery_1123("#billing\\:day").change(
    function () {
        day = jQuery_1123(this).val();

        if (!jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:day").val()
            || jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:day").val() == oldDay
            || changedAddress
        ) {
            jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:day").val(day);
            sendData(jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:day"));
        }

        if (!jQuery_1123('#overschrijving\\:payment\\:day').val()
            || jQuery_1123('#overschrijving\\:payment\\:day').val() == oldDay
            || changedAddress
        ) {
            jQuery_1123('#overschrijving\\:payment\\:day').val(day);
            sendData(jQuery_1123('#overschrijving\\:payment\\:day'));
        }

        oldDay = day;

        updateDob();
    }
);
jQuery_1123("#billing\\:month").change(
    function () {
        month = jQuery_1123(this).val();

        if (!jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:month").val()
            || jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:month").val() == oldMonth
            || changedAddress
        ) {
            jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:month").val(month);
            sendData(jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:month"));
        }

        if (!jQuery_1123('#overschrijving\\:payment\\:month').val()
            || jQuery_1123('#overschrijving\\:payment\\:month').val() == oldMonth
            || changedAddress
        ) {
            jQuery_1123('#overschrijving\\:payment\\:month').val(month);
            sendData(jQuery_1123('#overschrijving\\:payment\\:month'));
        }

        oldMonth = month;

        updateDob();
    }
);
jQuery_1123("#billing\\:year").change(
    function () {
        year = jQuery_1123(this).val();

        if (!jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:year").val()
            || jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:year").val() == oldYear
            || changedAddress
        ) {
            jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:year").val(year);
            sendData(jQuery_1123("#container_payment_method_buckaroo3extended_paymentguarantee #payment\\:year"));
        }

        if (!jQuery_1123('#overschrijving\\:payment\\:year').val()
            || jQuery_1123('#overschrijving\\:payment\\:year').val() == oldYear
            || changedAddress
        ) {
            jQuery_1123('#overschrijving\\:payment\\:year').val(year);
            sendData(jQuery_1123('#overschrijving\\:payment\\:year'));
        }

        oldYear = year;

        updateDob();
    }
);

jQuery_1123('#billing-address-select').change(
    function () {
        if (!jQuery_1123('#billing-address-select option:selected').val()) {
            changedAddress = true;
        } else if (jQuery_1123('#billing-address-select option:selected').val() == originalAddress) {
            changedAddress = false;
        }
    }
);

function updateDob()
{
    if (oldDay && oldMonth && oldYear && oldYear > 1900) {
        var fullDay = oldDay;
        if (fullDay < 10 && fullDay.charAt(0) != '0') {
            fullDay = '0' + oldDay;
        }

        var fullMonth = oldMonth;
        if (fullMonth < 10 && fullMonth.charAt(0) != '0') {
            fullMonth = '0' + oldMonth;
        }

        var fullDob = fullDay + '-' + fullMonth + '-' + oldYear;
        jQuery_1123("#billing\\:dob").val(fullDob);

        saveOscBilling();
    }
}
