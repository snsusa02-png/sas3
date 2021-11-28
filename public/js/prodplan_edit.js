$(document).ready(function () {

    function chk_lvltypeid() {
        var lvltypeid = $("#lvltypeid").val();
        //alert(lvltypeid);

        if (lvltypeid == '') { //уровень не выбран - скрываем все
            $(".l1h").hide();
            $(".l2h").hide();
            $(".l3h").hide();
        }
        if (lvltypeid == 1) { //группа - не редактируем кол-во и ЕИ
            $(".l1h").hide();
            $(".l1s").show();
        }
        if (lvltypeid == 2) {
            $(".l2h").hide();
            $(".l2s").show();
            $("#unit").attr('readonly', false);
        }
        if (lvltypeid == 3) {
            $(".l3h").hide();
            $(".l3s").show();
            $("#unit").attr('readonly', true);
        }
        // var refitmid = $("#refitmid").val();
        // var qty = $("#qty").val();
        // // console.log('qty=' + qty);
        // $.get("/refitems/getprice/", {refitmid: refitmid, orgid: orgid, qty: qty},
        //     function (data) {
        //         // console.log(data);
        //         // console.log(data.price);
        //         $("#price").val(data.price);
        //         $("#itmsum").val(data.price*qty);
        //
        //     }
        // )
    }

    $("#lvltypeid").change(function () {

        chk_lvltypeid();

    });



    chk_lvltypeid();

});
