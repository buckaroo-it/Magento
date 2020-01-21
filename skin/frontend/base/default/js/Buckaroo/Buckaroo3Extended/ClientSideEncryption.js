function getEncryptedData() {
    var cardNumber = jQuery_1123("#cardNumber").val();
    var cvc = jQuery_1123("#cvc").val();
    var cardHolderName = jQuery_1123("#cardHolderName").val();
    var expirationYear = jQuery_1123("#expirationYear").val();
    var expirationMonth = jQuery_1123("#expirationMonth").val();
    var cardNumberValid = BuckarooClientSideEncryption.V001.validateCardNumber(cardNumber);
    var cvcValid = BuckarooClientSideEncryption.V001.validateCvc(cvc);
    var cardHolderNameValid = BuckarooClientSideEncryption.V001.validateCardholderName(cardHolderName);
    var expirationYearValid = BuckarooClientSideEncryption.V001.validateYear(expirationYear);
    var expirationMonthValid = BuckarooClientSideEncryption.V001.validateMonth(expirationMonth);

    if (!cardNumberValid || !cvcValid || !cardHolderNameValid || !expirationYearValid || !expirationMonthValid) {
        return false;
    }

    BuckarooClientSideEncryption.V001.encryptCardData(cardNumber,
        expirationYear,
        expirationMonth,
        cvc,
        cardHolderName,
        function (encryptedCardData) {
            jQuery_1123("#buckaroo3extended_creditcard_encryptedCardData").val(encryptedCardData)
        });

    return true;
}

function removeSpaces(cardNumberElement)
{
    jQuery_1123(cardNumberElement).on({
        keydown: function(e) {
            if (e.which === 32)
                return false;
        },
        change: function() {
            this.value = this.value.replace(/\s/g, "");
        }
    });
}

function changeCardLogo(issuerElement)
{
    var sourcePath = jQuery_1123('.creditcard-logo').attr('src');
    var index = sourcePath.indexOf('/creditcard_types/');

    sourcePath = sourcePath.substring(0,index);

    jQuery_1123('.creditcard-logo').attr('src', sourcePath.replace(sourcePath, sourcePath + issuerElement.getAttribute('data-logo')));
}

Validation.add('validate-creditcard', 'Please enter a valid creditcard.', function (value) {
    return BuckarooClientSideEncryption.V001.validateCardNumber(value);
});

Validation.add('validate-cvc', 'Please enter a valid cvv / cvc code.', function (value) {
    return BuckarooClientSideEncryption.V001.validateCvc(value);
});

Validation.add('validate-cardholdername', 'Please enter a valid name.', function (value) {
    return BuckarooClientSideEncryption.V001.validateCardholderName(value);
});

Validation.add('validate-year', 'Please enter a valid year.', function (value) {
    return BuckarooClientSideEncryption.V001.validateYear(value);
});

Validation.add('validate-month', 'Please enter a valid month.', function (value) {
    return BuckarooClientSideEncryption.V001.validateMonth(value);
});

/** The button isn't ready yet on document load. This will create the event prematurely and gets triggered in the form.phtml. **/
jQuery("html").on("loadEncryptedDataFunction", function() {
    jQuery('#payment-buttons-container button:first').on('click', function() { getEncryptedData(); } );
});