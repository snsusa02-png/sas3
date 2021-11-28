$(document).ready(function () {

    $("input[name='expense_sum']").change(function () {
        //для старых браузеров
        var nval = parseFloat($(this).val().replace(',', '.').replace(' ', ''));
        var val = nval.toFixed(2);
        val = (isNaN(val) || val < 0) ? 0 : val;
        $(this).val(val);
    });

    function invoiceid_rfr() {
        var save_ID = $("#upd_id").val();
        //console.log('upd_id=' + save_ID);

        $("#upd_id > option").remove();

        $.get("/api/invoices/child_docs", {pdid: $("#invoiceid").val()},
            function (data) {
                //console.log(data);

                //$("#upd_id > option").remove()
                $("#upd_id").append($("<option>"))
                $.each(data.child_docs, function (index, value) {
                    $("#upd_id").append($("<option>").attr("value", index).append(value))
                });

                if (save_ID)
                    $("#upd_id").val(save_ID);
            }
        )
        $("#upd_id").val(save_ID);
    }

    $("#invoiceid").change(function () {
        invoiceid_rfr();
    });


    function budgets_rfr() {
        var save_ID = $("#bdgtitmsumid").val();
        console.log('bdgtitmsumid=' + save_ID);

        $("#bdgtitmsumid > option").remove();

        $.get("/api/equiprqst/upd/bdgtitmsums", {rqid: $("#rqstid").val(), did: $("#upd_id").val()},
            function (data) {
                //console.log(data);

                $("#bdgtitmsumid").append($("<option>"))
                $.each(data.list, function (index, value) {
                    $("#bdgtitmsumid").append($("<option>").attr("value", index).append(value))
                });
                if (save_ID)
                    $("#bdgtitmsumid").val(save_ID);
            }
        )
    }


    $("#upd_id").change(function () {
        budgets_rfr();
    });
});
