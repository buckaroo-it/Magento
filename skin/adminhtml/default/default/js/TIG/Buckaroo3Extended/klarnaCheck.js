document.observe(
    "dom:loaded", function () {
    var elements = $$('.buckaroo_fee');
    elements.each(
        function (element) {
        element.up(1).remove();
        }
    );
    }
);

