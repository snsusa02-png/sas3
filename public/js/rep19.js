$(document).ready(function () {

    function buildobjid_rfr() {
        var save_ID = $("#s_buildopertypeid").val();
        console.log('s_buildopertypeid=' + save_ID);
        $("#s_buildopertypeid > option").remove();
        //$("#buildopertypeid").css("background-color","#ffe1e1");

        $.get("/buildobjs/buildopertypes/params", {buildobjid: $("#s_buildobjid").val()},
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#s_buildopertypeid > option").remove()
                $("#s_buildopertypeid").append($("<option>"))
                $.each(data.opertypes, function (index, value) {
                    $("#s_buildopertypeid").append($("<option>").attr("value", index).append(value))
                });
            }
        )
        $("#s_buildopertypeid").val(save_ID);
    }

    $("#s_buildobjid").change(function () {
        //alert($("#s_buildobjid").val());
        buildobjid_rfr();
    });

    $("#s_budgetid").change(function () {
        var s_budgetid = $("#s_budgetid").val();
        //alert(s_budgetid);
        var tgt = '#s_buildopertypeid';
        $("#s_buildopertypeid > option").remove()
        $.get("/api/buildopertypes/for_budget", {budgetid: s_budgetid},
            function (data) {
                //console.log(data);
                $("#s_buildopertypeid > option").remove()
                $("#s_buildopertypeid").append($("<option>"))
                $.each(data.opertypes, function (index, value) {
                    $("#s_buildopertypeid").append($("<option>").attr("value", index).append(value))
                });
            }
        )
    });

});
