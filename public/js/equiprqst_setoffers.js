$(document).ready(function () {

    //добавление строки для состава
    $('#offers_table').on('click', 'button[name="cmdAddRow"]', function () {

        //var $curRow = $(this).closest('tr')

        var $curRow = $(this).closest('tr').next('.offer');
        var $srcRow = $("#sampleOffer");
        //var $newRow = $curRow.clone(true);
        var $newRow = $srcRow.clone(true);
        //console.log($curRow);

        //console.log($($curRow).find(".itemid").val());
        //id не нужен
        $newRow.attr("id", "");
        //привяжем к позиции заявки
        $newRow.find(".itemid").val($curRow.find(".itemid").val());
        //зачистим значения
        $newRow.find(".offerid").val('');
        $newRow.find(".invoiceid").val('').prop('required', true);
        $($newRow).find(".doc_qty").val('').prop('required', true);
        $($newRow).find(".doc_unit").val('').prop('required', true);
        $($newRow).find(".ord_qty").val('').prop('required', true);
        $($newRow).find(".unit").html($curRow.find(".unit").html());
        $($newRow).find(".ord_sum").val('').prop('required', true);
        $($newRow).find(".ord_price").val('');
        $($newRow).find(".plngetdate").val('').prop('required', true);

        $curRow.before($newRow);
        $newRow.show();
        // console.log('added');
    });

    $('#offers_table').on('click', 'button[name="cmdDelRow"]', function () {
        if (confirm('Удалить строку?')) {
            var $curRow = $(this).closest('tr');
            //console.log($curRow);

            //console.log($($curRow).find(".itemid").val());
            //зачистим значения
            $curRow.find(".status").val('D');
            $curRow.hide();

            $curRow.find(".invoiceid").val('').prop('required', false);
            $curRow.find(".doc_qty").val('').prop('required', false);
            $curRow.find(".doc_unit").val('').prop('required', false);
            $curRow.find(".ord_qty").val(0).prop('required', false);
            $curRow.find(".ord_sum").val(0).prop('required', false);
            $curRow.find(".ord_price").val(0).prop('required', false);
            $curRow.find(".plngetdate").val('').prop('required', false);

            recalcVals();
        }
    });


    function recalcVals() {

        var par;
        var qty, sum, price;
        var item_sums = [];
        var totsum = 0;


        $(".ord_sum").each(function (i, val) {

            par = $(val).parent().parent();
            //console.log(par);

            sum = parseFloat($(val).val());
            //console.log('sum='+sum);
            qty = parseFloat($(".qty", $(par)).val());

            //price = parseFloat($(".ord_price", $(par)).val());
            price = 0;
            if (qty > 0)
                price = (sum / qty).toFixed(6);

            //console.log('price='+price);
            $(".ord_price", $(par)).val(price);


            // рассчитаем частные суммы ---------------------------------
            //console.log($(val).val());
            var itemid = $(".itemid", $(par)).val();
            //console.log('itemid=' + itemid);
            rslt = item_sums.filter(obj => {
                return obj.itemid == itemid
            });
            if (rslt.length == 0) {
                item_sums.push({itemid: itemid, qty: 0, sum: 0});
                // повторить поиск
                rslt = item_sums.filter(obj => {
                    return obj.itemid == itemid
                });
            }
            rslt[0].qty += isNaN(qty) ? 0 : qty;
            rslt[0].sum += isNaN(sum) ? 0 : sum;
            //console.log(rslt[0]);
            //----------------------------------------------------------

            totsum += isNaN(sum) ? 0 : sum;
        });

        // отобразим суммы по позициям --------------------------------
        //console.log(item_sums);
        var fmtNum2f = new Intl.NumberFormat('ru-RU', {minimumFractionDigits: 2});
        var fmtNum3f = new Intl.NumberFormat('ru-RU', {minimumFractionDigits: 3});
        var fmtNum6f = new Intl.NumberFormat('ru-RU', {minimumFractionDigits: 6});

        item_sums.forEach(function (item, i, arr) {
            //alert( i + ": " + item + " (массив:" + arr + ")" );

            //раскрасим итоговую ячейку в зависимости от достаточности/недостаточности заказанного кол-ва
            rqst_qty = parseFloat($(('#rqst_qty_' + item.itemid)).html());
            ord_qty = item.qty;
            elm = $(('.tot_ordqty_' + item.itemid));
            elm.removeClass('not_enough').removeClass('enough').removeClass('oversize');
            if (ord_qty < rqst_qty) {
                elm.addClass('not_enough');
            } else if (ord_qty == rqst_qty) {
                elm.addClass('enough');
            } else if (ord_qty > rqst_qty) {
                elm.addClass('oversize');
            }

            //console.log(rqst_qty);
            $(('.tot_ordqty_' + item.itemid)).html(fmtNum3f.format(item.qty.toFixed(3)));
            $(('.tot_ordsum_' + item.itemid)).html(fmtNum2f.format(item.sum.toFixed(2)));
            if (item.qty > 0)
                $(('.avg_ordprice_' + item.itemid)).html(fmtNum6f.format((item.sum / item.qty).toFixed(6)));
        });
        //-------------------------------------------------------------

        $(".SumTotal").html(fmtNum2f.format((totsum).toFixed(2)));

    }


    $(".ord_qty,.ord_sum").change(function () {
        // if (isNaN($(this).val()))
        //     $(this).val('');

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

    $("#btnFillInv").click(function () {
        //"проливка" выбранным счетом

        ref_invoiceid = $("#ref_invoiceid").val();
        if (ref_invoiceid) {

            if (confirm("Выбраный счет будет указан во всех позициях. Продолжить?")) {

                //получим данные счета
                $.get("/api/invoice_items/items", {invoiceid: ref_invoiceid},
                    function (data) {
                        //кол-во позиций в ответе
                        itms_cnt = Object.keys(data.items).length;

                        if (itms_cnt == 0) {
                            $(".invitmid").hide();    //list
                            $(".itmname").val('').show();    //зачистим прежнее значение

                        } else {
                            //счет имеет состав => сформируем содержание выпадающего списка для каждой позиции
                            $(".invitmid > option").remove();   //удаляем опции вып.списков во всех select-ах
                            $(".invitmid").each(function (i, val) {
                                $(val).append($("<option>"))
                                $.each(data.items, function (index, value) {
                                    $(val).append($("<option>").attr("value", index).append(value))
                                });
                            });
                            $(".invitmid").show();
                            $(".itmname").hide();
                        }
                    }
                );


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
                $(val).val(ref_plngetdate);
            });
        }
    });

    $("#btnPlnGetWrkDays").click(function () {
        var v = $("#ref_plngetwrkdays").val();
        if (v) {
            //console.log(ref_plngetdate)
            $(".plngetwrkdays").each(function (i, val) {
                //console.log( $(val).val())
                $(val).val(v);
            });
        }
    });


    //заполнение списка составом счета
    $(".invoiceid").change(function () {
        $this = $(this);
        par = $(this).parent().parent();
        // console.log(par);
        // console.log(par.find(".invitmid"));

        par.find(".invitmid > option").remove();

        $.get("/api/invoice_items/items", {invoiceid: $this.val()},
            function (data) {
                //кол-во позиций в ответе
                itms_cnt = Object.keys(data.items).length;

                list = par.find(".invitmid")
                itmname = par.find(".itmname")

                if (itms_cnt == 0) {
                    list.hide();
                    itmname.val('');    //зачистим прежнее значение
                    itmname.show();

                } else {
                    list.append($("<option>"))
                    $.each(data.items, function (index, value) {
                        list.append($("<option>").attr("value", index).append(value))
                    });
                    list.show();
                    itmname.hide();
                }
                //зачистим прежние значения
                par.find(".doc_unit").val('');
                par.find(".doc_qty").val('');
                par.find(".ord_qty").val('');
                par.find(".ord_sum").val('');
                par.find(".ord_price").val('');
                //$("#tgt_addr").val(data.address);

            }
        )
    });

    //заполнение кол-ва и ЕИ из выбранной позиции счета
    $(".invitmid").change(function () {
        $this = $(this);
        par = $(this).parent().parent();
        // console.log(par);
        // console.log(par.find(".doc_qty"));
        // console.log($this.val());

        $.get("/api/invoice_items/item", {invitmid: $this.val()},
            function (data) {
                console.log(data.item);
                par.find(".itmname").val(data.item.itmname);
                par.find(".doc_qty").val(data.item.qty);
                par.find(".doc_unit").val(data.item.unit);
                par.find(".ord_sum").val(data.item.itmsum);
                par.find(".ord_price").val(data.item.price);

                console.log(par.find(".unit").html());
                console.log(data.item.unit);
                if (par.find(".unit").html() == data.item.unit) {
                    par.find(".ord_qty").val(data.item.qty);
                }

            }
        )
    });
});

function Cancel() {
    alert("/equiprqsts/" + $("#rqstid").val() + "#items");
    if (confirm("Отменить редактирование?"))
        document.location = "/equiprqsts/" + $("#rqstid").val() + "#items";
}
