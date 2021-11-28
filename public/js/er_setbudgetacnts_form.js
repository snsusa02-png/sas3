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

    $("#estdocsum").blur(function () {
        val = parsePotentiallyGroupedFloat($(this).val());
        //console.log("estdocsum=" + val);
        if (isNaN(val)) val = '';
        $(this).val(val);
    });

    $('#forEdit').submit(function () {

        val = parsePotentiallyGroupedFloat($("#estdocsum").val());
        if (isNaN(val)) val = '';
        $("#estdocsum").val(val);

    });


    /*
* Минималистичный калькулятор без eval
* Вводятся только целые, дробные числа и знаки .+*-/()
* Копированием вставляется только строка, которую можно вычислить
*/

    $('.smet_price_calc').keypress(function (e) {
        // только такие символы - "цифры", "точка"
        var regx = /^[\d.,()*+/\s-]$/;
        if (!e.key.match(regx))
            e.preventDefault();
    });

    $(".smet_price_calc").on("paste", function (e) {
        // access the clipboard using the api
        var pastedData = e.originalEvent.clipboardData.getData('text');
        //var rslt = pastedData.match(/[0-9()\+\-\*/\.]/g).join('');
        var rslt = pastedData.match(/[\d.()*+/\s-]/g).join('');
        //alert(pastedData+' -> '+ rslt);
        // (+13,228,652.40 + 33) /3 *2 -5
        //console.log(rslt);
        //e.originalEvent.clipboardData.setData('text',rslt);
        e.preventDefault();
        $(this).val(rslt);
        return rslt;
    });

    $('.calculate').click(function () {
        //Some code
        let calc = $(this).parent().find('.smet_price_calc').val();
        if (calc) {
            let result = $(this).parent().find('.smet_price');

            calc = calc.replace(',', '.');

            let getResult = new Function(`return ${calc}`);
            result.val(getResult().toFixed(2)); //вставить вычисленный результат в поле ввода
        }
    });

    $('.smet_price_calc').blur(function () {
        let calc = $(this).val();
        if (calc) {
            let result = $(this).parent().find('.smet_price');

            calc = calc.replace(',', '.');

            let getResult = new Function(`return ${calc}`);
            result.val(getResult().toFixed(2)); //вставить вычисленный результат в поле ввода
        }
    });


});
