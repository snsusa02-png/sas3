$(document).ready(function () {

    function chk_begsetmode() {
        $(".bt").hide();
        var setmode = $("#begsetmode").val();
        if (setmode)
            $(".bt" + setmode).show();
    }

    $("#begsetmode").change(function () {

        chk_begsetmode();

        // //console.log($("#orgid").val());
        // $("#par_contractid > option").remove();
        // $.get("/api/contracts/params", {orgid: $("#orgid").val(), ownorgid: $("#par_orgid").val()},
        //     function (data) {
        //         //console.log(data);
        //
        //         $("#par_contractid > option").remove()
        //         $("#par_contractid").append($("<option>"))
        //         $.each(data.contracts, function (index, value) {
        //             $("#par_contractid").append($("<option>").attr("value", index).append(value))
        //         });
        //     }
        // )

    });

    chk_begsetmode();

});
