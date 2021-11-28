$(document).ready(function () {

    function recalc_price() {
        var qty = parseFloat($("#qty").val());
        var itmsum = parseFloat($("#itmsum").val());
        var price = '';
        if(qty>0)
            price = itmsum/qty;
        $("#price").val(price);
    }

    $("#itmsum, #qty").change(function () {
        recalc_price();
    });


    $("#unittypeid").change(function () {
        //заполним шаг изменения кол-ва, приемлемый для выбранной ЕИ
        $.get("/unittypes/info/params", {id: $("#unittypeid").val()},
            function (data) {
                //console.log(data);
                $("#rqst_qty").prop('step', 1 / 10 ** data.decimal_dgts);
            }
        )
    });


    //заблокируем некоторые поля для позиций "из справочника": единицы измерения
    if ($("#refitmid").val()) {
        $('#unit').prop('readonly', 'readonly');
        $('#unittypeid').prop('disabled', true);
    } else {
        $('#unit').prop('readonly', '');
        $('#unittypeid').prop('disabled', false);
    }

});
