$(document).ready(function () {

    $("#exeorgid").change(function () {
        $("#exestaffid > option").remove()
        $.get("/buildobjs/staff/params", {orgid: $("#exeorgid").val()},
            function (data) {
                //console.log(data);
                //$("#tgt_addr").val(data.address);

                $("#exestaffid > option").remove()
                $("#exestaffid").append($("<option>"))
                $.each(data.staff, function (index, value) {
                    $("#exestaffid").append($("<option>").attr("value", index).append(value))
                });
            }
        )
    });


    function test_completed_check() {
        // if ($("#completed").prop("checked")) {
        //     $("#exe_report").show();
        // } else {
        //     $("#exe_report").hide();
        // }
        if ($("#completed").val()!='') {
            $("#exe_report").show();
        } else {
            $("#exe_report").hide();
        }

    }

    $("#completed").change(function () {
        test_completed_check();
    });

    test_completed_check();

});
