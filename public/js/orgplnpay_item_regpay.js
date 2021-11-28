$(document).ready(function () {


    $("#btnAgrSum2PaySum").click(function () {
        sum = $("#agr_sum").val();
        $("#fctpaysum").val(sum);
    });


    $("input[name='fctpaysum']").change(function () {
        //для старых браузеров
        var val = parseFloat($(this).val().replace(',', '.').replace(' ', '')).toFixed(2);
        val = (isNaN(val) || val<0) ? 0 : val;

        $(this).val(val);
    });
 });
