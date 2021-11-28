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

    function recalc_smet_sum(){
        var val = (parseFloat($("#lim_qty").val()) *  parseFloat($("#smet_price").val())).toFixed(2);
        $(('#smet_sum')).val(val);
    }


    $("#smet_price").blur(function () {
        val = parsePotentiallyGroupedFloat($(this).val());
        //console.log("estdocsum=" + val);
        if (isNaN(val)) val = '';
        $(this).val(val);

        recalc_smet_sum();
    });

    $('#forEdit').submit(function () {

        val = parsePotentiallyGroupedFloat($("#smet_price").val());
        if (isNaN(val)) val = '';
        $("#smet_price").val(val);

        recalc_smet_sum();

    });


    /*
* Минималистичный калькулятор без eval
* Вводятся только целые, дробные числа и знаки .+*-/()
* Копированием вставляется только строка, которую можно вычислить
*/

    $('#smet_price_calc').keypress(function (e) {
        // только такие символы - "цифры", "точка"
        var regx = /^[\d.,()*+/\s-]$/;
        if (!e.key.match(regx))
            e.preventDefault();
    });

    $("#smet_price_calc").on("paste", function (e) {
        // access the clipboard using the api
        var pastedData = e.originalEvent.clipboardData.getData('text');

        //убираем пробелы между триадами "123 456 779.33 + 33 / 2" -> "123456779.33 + 33 / 2"
        pastedData = pastedData.replace(/(\d)\s+(?=\d)/g, '$1');

        //var rslt = pastedData.match(/[0-9()\+\-\*/\.]/g).join('');
        var rslt = pastedData.match(/[\d.()*+/\s-]/g).join('');
        //alert(pastedData+' -> '+ rslt);
        // (13,228,652.40 + 33) /3 *2 -5
        //console.log(rslt);
        //e.originalEvent.clipboardData.setData('text',rslt);
        e.preventDefault();
        $(this).val(rslt);
        return rslt;
    });

    $('.calculate').click(function () {

        let calc = $(this).val();
        //убираем пробелы между триадами "123 456 779.33 + 33 / 2" -> "123456779.33 + 33 / 2"
        calc = calc.replace(/(\d)\s+(?=\d)/g, '$1');
        //убираем все лишнее (кроме цифр, операторов и пробелов)
        calc = calc.match(/[\d.()*+-\s/]/g).join('');
        $(this).val(calc)

        if (calc) {

            let getResult = new Function(`return ${calc}`);
            $("#smet_price").val(getResult().toFixed(2)); //вставить вычисленный результат в поле ввода

            recalc_smet_sum();

        }
    });

    $('#smet_price_calc').blur(function () {

        let calc = $(this).val();

        //убираем пробелы между триадами "123 456 779.33 + 33 / 2" -> "123456779.33 + 33 / 2"
        calc = calc.replace(/(\d)\s+(?=\d)/g, '$1');

        //убираем все лишнее (кроме цифр, операторов и пробелов)
        calc = calc.match(/[\d.()*+-\s/]/g).join('');
        $(this).val(calc)

        if (calc) {

            let getResult = new Function(`return ${calc}`);
            $("#smet_price").val(getResult().toFixed(2)); //вставить вычисленный результат в поле ввода

            recalc_smet_sum();

        }
    });


});
