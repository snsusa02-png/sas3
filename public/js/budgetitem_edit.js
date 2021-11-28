$(document).ready(function () {

    $("#buildopertypeid").change(function () {
        // alert($( "#buildopertypeid option:selected" ).text());
        $("#name").val($("#buildopertypeid option:selected").text());
    });

    // $("#src_orgid").change(function () {
    //     $.get("/orgs/info/params", {orgid: $("#src_orgid").val()},
    //         function (data) {
    //             console.log(data);
    //             $("#src_addr").val(data.address);
    //         }
    //     )
    // });

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
    // let calc = document.querySelector('#plnsum_calc'); //поле ввода
    // let result = document.querySelector('#plnsum'); //поле вывода
    // let calculate = document.querySelector('.calculate'); //кнопка вычислить
    // let isPasteFlag = false; //флаг - было ли значение вставлено
    // let savedValueBeforePaste = '';//сохранить значение которое было до вставки
    //
    // //обработчик сработает при изменении значения в поле - ввод, вставка, удаление Backspace
    // calc.addEventListener('input', (e) => {
    //     if (!calc.value) return; //если значения нет - выход (при Backspace)
    //     if (isPasteFlag) { //если значение было скопировано и вставлено
    //         if (calc.value.search(/[^\d-+*/.()]/g) !== -1) { //если во вставленной строке есть что-то кроме цифр и знаков .+*-/(), значит строка не вычислится
    //             calc.value = savedValueBeforePaste; // установить значение которое было до вставки
    //             isPasteFlag = false; // установить флаг в первоначальное состояние
    //             savedValueBeforePaste = '';// установить в первоначальное состояние
    //         }
    //     }
    //     //ввод значений с клавиатуры
    //     else {
    //         let lastValue = calc.value[calc.value.length - 1];// получить последний введенный символ
    //         if (lastValue.search(/\d|[-+*/.()]/) === -1) { // если последний введенный символ не цифра и не знаки .+*-/()
    //             calc.value = calc.value.slice(0, -1); //удалить последний символ
    //         }
    //     }
    // });
    //
    // //обработчик сработает при вставке скопированного значения
    // calc.addEventListener('paste', (e) => {
    //     savedValueBeforePaste = calc.value;// сохранить значение до вставки
    //     isPasteFlag = true; //вставка была
    // });
    //
    // //обработчик сработает при клике по кнопке
    // calculate.addEventListener('click', (e) => {
    //     if (!calc.value) return; //если значения нет - выход
    //     //new Function как и eval вычислит строку
    //     let getResult = new Function(`return ${calc.value}`);
    //     result.value = getResult().toFixed(2); //вставить вычисленный результат в поле ввода
    // });
    //
    // calc.addEventListener('keydown', function (e) {
    //     // console.log(e.key)
    //     // console.log(e.keyCode)
    //     if (e.key === 'Enter' || e.key === 'Tab') {
    //
    //         if (!calc.value) return; //если значения нет - выход
    //         //new Function как и eval вычислит строку
    //         let getResult = new Function(`return ${calc.value}`);
    //         //calc.value = getResult(); //вставить вычисленный результат в поле ввода
    //         result.value = getResult().toFixed(2); //вставить вычисленный результат в поле ввода
    //     }
    // });


    $('#plnsum_calc').keypress(function (e) {
        // только такие символы - "цифры", "точка"
        var regx = /^[\d.,()*+/\s-]$/;
        if (!e.key.match(regx))
            e.preventDefault();
    });

    $("#plnsum_calc").on("paste", function (e) {
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
            $("#plnsum").val(getResult().toFixed(2)); //вставить вычисленный результат в поле ввода
        }
    });

    $('#plnsum_calc').blur(function () {

        let calc = $(this).val();

        //убираем пробелы между триадами "123 456 779.33 + 33 / 2" -> "123456779.33 + 33 / 2"
        calc = calc.replace(/(\d)\s+(?=\d)/g, '$1');

        //убираем все лишнее (кроме цифр, операторов и пробелов)
        calc = calc.match(/[\d.()*+-\s/]/g).join('');
        $(this).val(calc)

        if (calc) {

            let getResult = new Function(`return ${calc}`);
            $("#plnsum").val(getResult().toFixed(2)); //вставить вычисленный результат в поле ввода
        }
    });


});
