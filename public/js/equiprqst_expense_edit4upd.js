$(document).ready(function () {

    $("input[name='expense_sum']").change(function () {
        //для старых браузеров
        var nval = parseFloat($(this).val().replace(',', '.').replace(' ', ''));
        var val = nval.toFixed(2);
        val = (isNaN(val) || val < 0) ? 0 : val;
        $(this).val(val);
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


    $("#rqstid").change(function () {
        budgets_rfr();
    });
});
