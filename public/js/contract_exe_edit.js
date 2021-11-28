$(document).ready(function () {

    function calc_docsum() {
        var result = 0;
        var n = 0;
        $(".calc").each(function (index) {
            // console.log($(this).val())
            n = parseFloat($(this).val())
            //console.log(n)
            if (!isNaN(n))
                result += n;
        })
        //console.log(`result=${result}`);
        //результат расчета суммы составляющих
        $("#docsum").val(result.toFixed(2));
        //зачистим формулу для общей суммы документа
        $("#docsum").parent().find('.calc_formula').val('');

    }

    $(".calc_result").change(function () {
        //alert($(this).val());

        //обнулим формулу, если вносим сумму вручную
        $(this).parent().find('.calc_formula').val('');
    });

    $(".calc").change(function () {
        //alert($(this).val());

        //рассчитаем общую сумму
        calc_docsum();
    });

    $("#docsum").change(function () {
        //прямое редактирование общей суммы документа => зачистим все формулы и суммы составляющих
        $(".calc").each(function (index) {
            $(this).val('');
            $(this).parent().find('.calc_formula').val('');
        })
    });

    $("[name='docsum_calc']").change(function () {
        //прямое редактирование общей суммы документа => зачистим все формулы и суммы составляющих
        $(".calc").each(function (index) {
            $(this).val('');
            $(this).parent().find('.calc_formula').val('');
        })
    });


    /*
* Минималистичный калькулятор без eval
* Вводятся только целые, дробные числа и знаки .+*-/()
* Копированием вставляется только строка, которую можно вычислить
*/

    $('.calc_formula').keypress(function (e) {
        // только такие символы - "цифры", "точка"
        var regx = /^[\d.,()*+/\s-]$/;
        if (!e.key.match(regx))
            e.preventDefault();
    });

    $(".calc_formula").on("paste", function (e) {
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
        let calc = $(this).parent().find('.calc_formula').val();
        if (calc) {
            let result = $(this).parent().find('.calc_result');

            calc = calc.replace(',', '.');

            let getResult = new Function(`return ${calc}`);
            result.val(getResult().toFixed(2)); //вставить вычисленный результат в поле ввода

            if (result.attr('name') != 'docsum' && result.attr('name') != 'm15_sum')
                calc_docsum();
        }
    });

    $('.calc_formula').blur(function () {
        let calc = $(this).val();
        if (calc) {
            let result = $(this).parent().find('.calc_result');

            calc = calc.replace(',', '.');

            let getResult = new Function(`return ${calc}`);
            result.val(getResult().toFixed(2)); //вставить вычисленный результат в поле ввода

            if (result.attr('name') != 'docsum' && result.attr('name') != 'm15_sum')
                calc_docsum();
        }
    });


});
