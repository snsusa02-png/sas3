$(document).ready(function () {

    $("#s_buildobjid").change(function () {
        var s_buildobjid = $("#s_buildobjid").val();
        //alert(s_budgetid);
        var tgt = '#s_budgetid';
        let save_s_budgetid = $("#s_budgetid").val();
        let save_s_buildopertypeid = $("#s_buildopertypeid").val();
        $("#s_budgetid > option").remove();
        $("#s_buildopertypeid > option").remove();

        $.get("/api/budgets/for_", {buildobjid: s_buildobjid, in_equiprsts: 1},
            function (data) {
                //console.log(data);
                $("#s_budgetid").append($("<option>"))
                $.each(data.list, function (index, value) {
                    $("#s_budgetid").append($("<option>").attr("value", index).append(value))
                });
                //попытка восстановления ранее выбранных значений
                $("#s_budgetid").val(save_s_budgetid);
                $("#s_buildopertypeid").val(save_s_buildopertypeid);
            }
        )
    });

    $("#s_budgetid").change(function () {
        var s_budgetid = $("#s_budgetid").val();
        //alert(s_budgetid);
        var tgt = '#s_buildopertypeid';
        $("#s_buildopertypeid > option").remove()
        $.get("/api/buildopertypes/for_budget", {budgetid: s_budgetid},
            function (data) {
                //console.log(data);
                //$("#s_buildopertypeid > option").remove()
                $("#s_buildopertypeid").append($("<option>"))
                $.each(data.opertypes, function (index, value) {
                    $("#s_buildopertypeid").append($("<option>").attr("value", index).append(value))
                });
            }
        )
    });

});
