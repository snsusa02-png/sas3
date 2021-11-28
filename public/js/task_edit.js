$(document).ready(function () {


    //$("#buildobjid").change();

    function chk_status() {
        var progress = $("#statusid").val();
        if (progress == 2 || progress == 3 || progress == 4)
            $("#progress_div").show();
        else
            $("#progress_div").hide();
    }

    chk_status();

    $("#statusid").change(function () {
        chk_status();
    });


    function chk_plnbegdt() {
        var chk_val = $("#plnbegdt").val();
        if (chk_val)
            $("#notify_div").show();
        else
            $("#notify_div").hide();
    }
    chk_plnbegdt();

    $("#plnbegdt").change(function () {
        chk_plnbegdt();
    });

    $("#categoryid,#ownorgid").change(function () {
        //скрываем кнопку загрузки рег. номера при любом изменении данных. Пусть сначала сохранят изменения
        $("#getregnum").hide();
    });

    $("#getregnum").click(function () {
        if (!$("#regnum").val()) {

            $.get("/contracts/fill_regnum/params", {contractid: $("#id").val()},
            )
                .done(function (data) {
                    //alert("Data Loaded: " + data);
                    //console.log(data);
                    $("#regnum").val(data.regnum);
                    $("#regnum").attr('readonly', 'readonly');
                    $("#getregnum").hide();
                });
        }
    });


    $("#setregnum").click(function () {
        //alert($(this).checked());
        if ($('#setregnum').prop('checked')) {
            $("#set_regnum").val(1);
        } else {
            $("#set_regnum").val(0);
        }
    });

});
