$(document).ready(function () {

    $("#doctypeid").change(function () {
        $.get("/stock/wrhdoctypes/params/", {doctypeid: $("#doctypeid").val()},
            function (data) {
                //console.log(data);

                lbl = data.ownorg_label;
                if (lbl == '') lbl = 'Владелец';
                $("#ownorg_label").html(lbl + ':');

                lbl = data.wrh_label;
                if (lbl == '') lbl = 'Склад';
                $("#wrh_label").html(lbl + ':');

                lbl = data.box_label;
                if (lbl == '') lbl = 'Отделение';
                $("#box_label").html(lbl + ':');

                if (data.need_relwrh == "1") {
                    lbl = data.relwrh_label;
                    if (lbl == '') lbl = 'Связанный склад';
                    $("#relwrh_label").html(lbl + ':');
                    $("#relwrh").show()

                    lbl = data.relbox_label;
                    if (!lbl || lbl == '') lbl = 'Связанное отделение';
                    $("#relbox_label").html(lbl + ':');
                    $("#relbox").show()
                } else {
                    $("#relwrh").hide();
                    $("#relbox").hide()
                }

                if (data.need_predoc == "1") {
                    $("#predoc").show()
                }
                else $("#predoc").hide();
            }
        )
    });


    $("#wrhid").change(function () {
        //alert($("#wrhid").val());

        $("#boxid > option").remove()
        $.get("/api/wrh_boxes/for_", {wrhid: $("#wrhid").val()},
            function (data) {
                //console.log(data);

                $("#boxid > option").remove()
                $("#boxid").append($("<option>"))
                $.each(data.boxes, function (index, value) {
                    $("#boxid").append($("<option>").attr("value", index).append(value))
                });

                // $("#need_relwrh").val(data.need_relwrh);
                // $("#need_predoc").val(data.need_predoc);

                // if (data.need_relwrh == "1") $("#relwrh").show()
                // else $("#relwrh").hide();
                //
                // if (data.need_predoc == "1") $("#predoc").show()
                // else $("#predoc").hide();
            }
        )
    });

    $("#relwrhid").change(function () {
        //alert($("#wrhid").val());

        $("#relboxid > option").remove()
        $.get("/api/wrh_boxes/for_", {wrhid: $("#relwrhid").val()},
            function (data) {
                //console.log(data);

                $("#relboxid > option").remove()
                $("#relboxid").append($("<option>"))
                $.each(data.boxes, function (index, value) {
                    $("#relboxid").append($("<option>").attr("value", index).append(value))
                });
            }
        )
    });


});
