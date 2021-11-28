$(document).ready(function () {

    function parsePotentiallyGroupedFloat(stringValue) {
        //Преобразует форматированное число в float
        // * 1 234 678.99  -> 1234678.99
        // * 1,234,678.99  -> 1234678.99
        stringValue = stringValue.trim();

        var result = stringValue.replace(/[^0-9]/g, '');
        if (/[,\.]\d{2}$/.test(stringValue)) {
            result = result.replace(/(\d{2})$/, '.$1');
        }
        if (/[,\.]\d{1}$/.test(stringValue)) {
            result = result.replace(/(\d{1})$/, '.$1');
        }
        return parseFloat(result);
    }

    $("#opersum").blur(function () {

        val = parsePotentiallyGroupedFloat($(this).val());
        if (isNaN(val)) val = '';
        $(this).val(val);
    });

    $('#forEdit').submit(function () {

        val = parsePotentiallyGroupedFloat($("#opersum").val());
        if (isNaN(val)) val = '';
        $("#opersum").val(val);

    });

});
