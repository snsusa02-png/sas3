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
    var totsum = 0, sum, j = 0
    $(".totsum").each(function (i, val) {
        par = $(val).parent()
        price = parseFloat($(".price", $(par)).html())
        cnt = parseFloat($(".cnt", $(par)).val())
        sum = price * cnt
        $(val).html((sum).toFixed(2))
        totsum += sum
    })
    $(".SumTotal").html((totsum).toFixed(2))
}



$("#btnFillItmSumId").click(function () {

    ref_id = $("#ref_bdgtitmsumid").val();
    if (ref_id) {
        if (confirm("Статья бюдежта по всем позициям будет заменена на выбранную. Продолжить?")) {
            $(".itmsumid").each(function (i, val) {
                $(val).val(ref_id);
            });
        }
    }
});


function Cancel() {
    if (confirm("Отменить редактирование?"))
        document.location = "/equiprqsts/" + $("#rqstid").val();
}

function chkorder() {
    var str
    $(".cnt").removeClass("err")
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

    var orditems = new Array();
    var itm = {};
    $(btn).html('<i class="fa fa-spinner fa-pulse fa-1x fa-fw"></i><span class="sr-only">Сохранение</span>');
    $("#btnCancel").hide();

    $(".item").each(function (i, val) {

        itm.id = $(val).attr("tid");
        //itm.qty = $(".cnt", $(val)).val();
        itm.itmsumid = $(".itmsumid", $(val)).val();

        orditems.push(_.clone(itm));
    });
    var budgetitmid = $("#budgetitmid").val();
    console.log(orditems);
    // alert(1);

    $.ajax({
        // url: "/orditems/edtqty/save",
        url: "/equiprqst_items/setbdgtacnts/save",
        type: "post",
        data: {"rqstid": $("#rqstid").val(), "budgetitmid": budgetitmid, "orditems": orditems},
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

