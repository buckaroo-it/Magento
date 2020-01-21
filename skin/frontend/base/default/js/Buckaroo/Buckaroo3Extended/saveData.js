jQuery_1123(document).on(
    'change', '.buckaroo3extended_input input, .buckaroo3extended_input select', function () {
        sendData(jQuery_1123(this));
    }
);

jQuery_1123(document).on(
    'change', '#buckaroo3extended_directdebit_account_owner, #buckaroo3extended_directdebit_account_number', function () {
        sendData(jQuery_1123(this));
    }
);
