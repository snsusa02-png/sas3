function setCnt(div, i) {

    var cntDiv = $(".cnt", $(div).parent().parent())
    var cnt = parseInt($(cntDiv).val())


    cnt += i
    if (cnt < 0) cnt = 0

    $(cntDiv).val(cnt)
    recalcVals()

}


function recalcVals() {
    var par;
    var cnt, price;
    var totsum = 0, sum, j = 0, totNA = 0;

    $(".linesum").each(function (i, val) {
        par = $(val).parent();
        //console.log(par);
        //price = parseFloat($(".est_price", $(par)).html());
        if ($(".est_price", $(par)).val() === '') {
            //console.log($(".est_price", $(par)).val())
            totNA++;
        }

        price = parseFloat($(".est_price", $(par)).val());
        //console.log(price);
        qty = parseFloat($(".qty", $(par)).val());
        sum = price * qty;
        if (!isNaN(sum)) {
            $(val).html((sum).toFixed(2));
            totsum += sum;
        }else  $(val).html('-');
    });
    $(".SumTotal").html((totsum).toFixed(2))

    if (totNA > 0)
        $("#btnSave").html('<i class="fa fa-floppy-o" aria-hidden="true"></i> Сохранить').removeClass('btn-success').addClass('btn-info');
    else
        $("#btnSave").html('<i class="fa fa-check-square-o" aria-hidden="true"></i> Согласовать').removeClass('btn-info').addClass('btn-success');
}

function Cancel() {
    if (confirm("Отменить редактирование?"))
        document.location = "/equiprqsts/" + $("#rqstid").val();
}

function chkorder() {
    var str;
    $(".cnt").removeClass("err");
    $("#Msg").html("")
    var hasErr = false
    $(".cnt").each(function (i, val) {
        str = $(val).val()
        //str='a9';
        // console.log(parseFloat(str));
        str = parseFloat(str);
        if (isNaN(str)) {
            $("#Msg").html("Недопустимое значение количества!");
            $(val).addClass("err");
            hasErr = true
        } else $(val).val(str);
    })

    return !hasErr;
}

function saveOrder(btn) {

    window.onbeforeunload = null;   //убрать контроль выхода со странице с активными изменениями в полях формы,
                                    // так как здесь ajax-post

    if (!chkorder()) return;

    var items = new Array();
    var itm = {};
    $(btn).html('<i class="fa fa-spinner fa-pulse fa-1x fa-fw"></i><span class="sr-only">Сохранение</span>');
    $("#btnCancel").hide();

    $(".item").each(function (i, val) {

        itm.id = $(val).attr("tid");
        //itm.qty = $(".cnt", $(val)).val();
        itm.price = $(".est_price", $(val)).val();

        items.push(_.clone(itm));
    });
    //var budgetitmid = $("#budgetitmid").val();
    //console.log(items);
    // alert(1);

    $.ajax({
        // url: "/orditems/edtqty/save",
        url: "/equiprqst_items/setestprices/save",
        type: "post",
        data: {"rqstid": $("#rqstid").val(), "orditems": items},
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            //alert(1);
            document.location = "/equiprqsts/" + $("#rqstid").val()
        },
        error: function (msg) {
            $("#Msg").html("Ошибка при сохранении. Повторите попытку позднее.")
        }
    })

}

function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31
        && (charCode < 48 || charCode > 57))
        return false;

    return true;
}
