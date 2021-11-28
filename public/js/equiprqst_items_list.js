function myFunction() {
    //скрытие рядов таблицы состава заявки, которые в названии материала не содежрэат нужный текст
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("myInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("myTable");
    //tr = table.getElementsByTagName("tr.item");
    tr = table.querySelectorAll("tr.item");
    //console.log(tr);

    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[1];
        if (td) {
            txtValue = td.textContent || td.innerText;
            //console.log(txtValue)
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}

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
    var totsum = 0, sum, j = 0;
    $(".linesum").each(function (i, val) {
        par = $(val).parent();
        //console.log(par);
        //price = parseFloat($(".smet_price", $(par)).val());
        price = parseFloat($(".smet_price", $(par)).html());
        //console.log(price);
        qty = parseFloat($(".qty", $(par)).val());
        sum = price * qty;
        if (isNaN(sum)) {
            $(val).html('');
        } else {
            $(val).html((sum).toFixed(2));
            $(val).val((sum).toFixed(2));
            totsum += sum;
        }
    });
    $(".SumTotal").html((totsum).toFixed(2))
}

function recalcVals1() {
    var par;
    var cnt, price;
    var totsum = 0, sum, j = 0;
    $(".ord_sum").each(function (i, val) {
        par = $(val).parent().parent();
        //console.log(par);
        //price = parseFloat($(".est_price", $(par)).html());
        price = parseFloat($(".ord_price", $(par)).val());
        //console.log(price);
        qty = parseFloat($(".qty", $(par)).val());
        sum = price * qty;
        //$(val).html((sum).toFixed(2));
        $(val).val((sum).toFixed(2));
        totsum += sum;
    });
    $(".SumTotal").html((totsum).toFixed(2))
}

function Cancel() {
    if (confirm("Отменить редактирование?"))
        document.location = "/equiprqsts/" + $("#rqstid").val();
}

function chkorder() {
    var hasErr = false;
    var qty;
    var o_plngetdate;

    $("#msg").html("");
    $(".plngetdate").removeClass("err");

    $(".qty").each(function (i, val) {
        qty = parseFloat($(val).val());

        if (!isNaN(qty) && qty > 0) {
            //console.log(qty);
            //найдем соотв. дату и проверим ее пустоту
            o_plngetdate = $(val).parent().parent().find('.plngetdate');
            //console.log('plngetdate='+$(o_plngetdate).val());
            if (!$(o_plngetdate).val()) {
                $("#msg").html("Укажите дату поставки для всех заказываемых позиций!");
                $(o_plngetdate).addClass("err");
                hasErr = true;
            }
        } else {
            //скрыть строку без кол-ва
            $(val).parent().parent().hide();
        }
        ;
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


$(".qty").change(function () {
    recalcVals();
});

$(".btnAllQty").click(function () {
    par = $(this).parent();
    //console.log(par);
    max_qty = parseFloat($(".max_qty", $(par)).html());
    //console.log(max_qty);
    $(".qty", $(par).parent()).val(max_qty);
    recalcVals();
});

$("#btnGetQtyFromEst").click(function () {

    if (confirm("Количество будет заполнено запрошенным количеством по всем позициям. Продолжить?")) {
        $(".rqst_qty").each(function (i, val) {
            par = $(val).parent().parent();
            //console.log(par);

            rqst_qty = parseFloat($(val).html());
            //console.log('rqst_qty='+rqst_qty);

            // ord_qty = $(".ord_qty", $(par)).val();
            // console.log(ord_qty);
            $(".ord_qty", $(par)).val(rqst_qty);

        });
        recalcVals();
    }
});

$("#btnGetPriceFromEst").click(function () {

    if (confirm("Цена заказа будет заполнена оценочной ценой по всем позициям. Продолжить?")) {
        $(".est_price").each(function (i, val) {
            par = $(val).parent().parent();

            est_price = parseFloat($(val).html());
            $(".ord_price", $(par)).val(est_price);

        });
        recalcVals();
    }
});

$("#btnFillSup").click(function () {

    ref_suporgid = $("#ref_suporgid").val();
    if (ref_suporgid) {
        if (confirm("Поставщики по всем позициям будут заменены на выбранного. Продолжить?")) {
            $(".suporgid").each(function (i, val) {
                $(val).val(ref_suporgid);
            });
        }
    }
});

$("#btnFillInv").click(function () {

    ref_invoiceid = $("#ref_invoiceid").val();
    if (ref_invoiceid) {
        if (confirm("Выбраный счет будет указан во всех позициях. Продолжить?")) {
            $(".invoiceid").each(function (i, val) {
                $(val).val(ref_invoiceid);
            });
        }
    }
});

$("#btnPlnGetDate").click(function () {
    ref_plngetdate = $("#ref_plngetdate").val();

    if (ref_plngetdate) {
        //console.log(ref_plngetdate)
        $(".plngetdate").each(function (i, val) {
            //console.log( $(val).val())
            //console.log($(val).parent().parent().find('.qty').val());
            if (parseFloat($(val).parent().parent().find('.qty').val()) > 0)
                $(val).val(ref_plngetdate);
        });
    }
});

$(document).ready(function () {

    $(document).on('submit', 'form', function () {
        // code
        //console.log('submit');
        return chkorder();
    });

});
