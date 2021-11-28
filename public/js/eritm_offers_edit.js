$(document).ready(function () {


    $("#doc_unit").change(function () {
        $(".doc_unit").html($("#doc_unit").val());
    });


    function recalc_docsum() {

        var qty = parseFloat($("#doc_qty").val());
        var price = parseFloat($("#doc_price").val());
        var sum = Math.round(qty * price * 100) / 100;
        //console.log(qty * price);
        $("#doc_sum").val(sum);
        $("#ord_sum").val(sum);

        recalc_ordprice();

    }

    $("#doc_sum").change(function () {

        var sum = parseFloat($(this).val());
        var qty = parseFloat($("#doc_qty").val());
        var price = '';
        if(qty>0)
            // price = toString(Math.round(sum / qty * 10000) / 10000).replace(/0+$/,'');
            price = Math.round(sum / qty * 1000000) / 1000000;

        $("#doc_price").val(price);
        $("#ord_sum").val(sum);

        recalc_ordprice();

//        recalc_docsum();
    });

    $("#doc_qty").change(function () {

        var qty = parseFloat($("#doc_qty").val());
        var sum = parseFloat($("#doc_sum").val());

        var price = '';
        if(qty>0)
            price = Math.round(sum / qty * 10000) / 10000;

        $("#doc_qty").val(qty);
        $("#doc_price").val(price);

    });

    $("#doc_price").change(function () {
        var price = parseFloat($(this).val());
        var qty = parseFloat($("#doc_qty").val());
        var sum = Math.round(price * qty * 100) / 100;

        $("#doc_price").val(price);
        $("#doc_sum").val(sum);
        $("#ord_sum").val(sum);

        recalc_ordprice();
    });

    // $("#doc_price, #doc_qty").change(function () {
    //     recalc_docsum();
    // });

    function recalc_ordsum() {

        var qty = parseFloat($("#ord_qty").val());
        var price = parseFloat($("#ord_price").val());
        //console.log(qty * price);
        var sum = Math.round(qty * price * 100) / 100;
        $("#ord_sum").val(sum);

        // if ($("#out_price").val() == '') {
        //     $("#out_price").val(price);
        // }
    }


    $("#ord_price").change(function () {
        recalc_ordsum();
    });


    function recalc_ordprice() {

        var sum = parseFloat($("#ord_sum").val());
        var qty = parseFloat($("#ord_qty").val());

        var price = 0;
        if(qty>0){
            price = Math.round(sum/ qty * 1000000) / 1000000;
        }
        $("#ord_price").val(price);

    }

    $("#ord_sum, #ord_qty").change(function () {
        recalc_ordprice();
    });


    // $("#src_orgid").change(function () {
//     $.get("/orgs/info/params", {orgid: $("#src_orgid").val()},
//         function (data) {
//             console.log(data);
//             $("#src_addr").val(data.address);
//         }
//     )
// });


    $("#rqst_qty").change(function () {
        recale_est_sum()
    });
    $("#est_price").change(function () {
        recale_est_sum()
    });


    $('#forOrder').submit(function () {

        $("#doc_sum").val($("#ord_sum").val());

    });

    //buildobjid_rfr();
//$("#car_orgid").change();
    var fctenddt_setbyuser = false;

})
;
