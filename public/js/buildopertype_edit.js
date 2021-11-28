$(document).ready(function () {

    $("#set_estbudget").click(function () {
        var s1 = parseFloat($("#mat_totsum").val());
        var s2 = parseFloat($("#wrk_totsum").val());
        var s = Math.round(100*(s1 + s2))/100;
        $("#plnbudgetsum").val(s);
    })

    $("#orgid,#ownorgid").change(function () {
        //alert($("#orgid").val());
        $("#contractid > option").remove()
        $.get("/buildobjs/contracts/params", {orgid: $("#orgid").val(), ownorgid: $("#ownorgid").val()},
            function (data) {
                //console.log(data);
                //$("#tgt_addr").val(data.address);

                $("#contractid > option").remove()
                $("#contractid").append($("<option>"))
                $.each(data.contracts, function (index, value) {
                    $("#contractid").append($("<option>").attr("value", index).append(value))
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


    //$("#buildobjid").change();

});
