$(document).ready(function () {

    $("#s_timestatuscode").change(function () {

        var code = $("#s_timestatuscode").val();
        // console.log(code);

        if (code == 5) {
            $("#s_plndate").show();
        }
        else {
            $("#s_plndate").hide();
        }
    });



    $("#s_timestatuscode").change();

});
