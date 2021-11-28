$(document).ready(function () {

    function recalc_ordsum() {
        var qty = parseFloat($("#ord_qty").val());
        var price = parseFloat($("#ord_price").val());
        //console.log(qty * price);
        $("#ord_sum").val(qty * price);

        if ($("#out_price").val() == '') {
            $("#out_price").val(price);
        }
    }

    function chk_ord_decision() {
        if ($("#ord_decision").val() === '0') {
            //hide Yes
            $(".ord_yes").hide(200);
            $(".ord_no").show(100);
        } else if ($("#ord_decision").val() === '1') {
            //show Yes
            $(".ord_yes").show(100);
            $(".ord_no").hide(200);
        } else {
            //hide All
            $(".ord_yes").hide();
            $(".ord_no").hide();
        }

    }

    $("#lim_itmname").change(function () {
        if ($("#lim_itmname") && $("#lim_itmname").val().length == 0)
            $("#lim_refitmid").val('');
    });

    $("#ord_decision").change(function () {
        //alert($("#ord_decision").val());
        chk_ord_decision();
    });


    $("#ord_price, #ord_qty").change(function () {
        recalc_ordsum();
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


    function recale_est_sum() {
        price = parseFloat($("#est_price").val());
        //console.log(price);
        qty = parseFloat($("#rqst_qty").val());
        sum = price * qty;
        if (isNaN(sum))
            $("#est_sum").val('');
        else
            $("#est_sum").val((sum).toFixed(2));

    }

    $("#rqst_qty").change(function () {
        recale_est_sum()
    });
    $("#est_price").change(function () {
        recale_est_sum()
    });


    var fctenddt_setbyuser = false;

    chk_ord_decision();


    //заблокируем некоторые поля для позиций "из справочника": единицы измерения
    if ($("#refitmid").val()) {
        $('#unit').prop('readonly', 'readonly');
        $('#unittypeid').prop('disabled', true);
    } else {
        $('#unit').prop('readonly', '');
        $('#unittypeid').prop('disabled', false);
    }

});
