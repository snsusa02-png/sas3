$(document).ready(function () {

    function buildobjid_rfr() {
        $("#buildopertypeid > option").remove()
        $.get("/buildobjs/buildopertypes/params", {buildobjid: $("#buildobjid").val()},
            function (data) {
                //console.log(data);
                $("#deli_address").val(data.address);

                //var validOpers = validOpers.split(',');
                $("#buildopertypeid > option").remove()
                $("#buildopertypeid").append($("<option>"))
                $.each(data.opertypes, function (index, value) {
                    $("#buildopertypeid").append($("<option>").attr("value", index).append(value))
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
    }

    function budgetitms_rfr() {
        $("#budgetitmid > option").remove();

        if ($("#initorgid").val() && $("#buildobjid").val()) {
            $.get("/api/budgetitms/org/buildobj/"
                , {orgid: $("#initorgid").val(), buildobjid: $("#buildobjid").val()},
                function (data) {
                    console.log(data);

                    //var validOpers = validOpers.split(',');
                    $("#budgetitmid > option").remove()
                    $("#budgetitmid").append($("<option>"))
                    $.each(data.budgetitms, function (index, value) {
                        $("#budgetitmid").append($("<option>").attr("value", index).append(value))
                    });
                }
            )
        }
    }

    $("#initorgid").change(function () {
        //alert($("#initorgid").val());
        budgetitms_rfr();
    });

    $("#buildobjid").change(function () {
        //alert($("#buildobjid").val());
        buildobjid_rfr();

        budgetitms_rfr();
    });

    // $("#src_orgid").change(function () {
    //     $.get("/orgs/info/params", {orgid: $("#src_orgid").val()},
    //         function (data) {
    //             console.log(data);
    //             $("#src_addr").val(data.address);
    //         }
    //     )
    // });


    //buildobjid_rfr();
    //$("#car_orgid").change();
    var fctenddt_setbyuser = false;

});
