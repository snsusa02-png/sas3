$(document).ready(function () {

    function orgs_rfr() {
        var save_id = $("#s_orgid").val() ?? 0;

        $("#s_orgid > option").remove();
        //console.log($("#s_contractid >option"));

        if ($("#s_buildobjid").val()) {
            $.get("/api/orgs/for_", {
                    in_budgets_for_buildobjid: $("#s_buildobjid").val(),
                },
                function (data) {

                    $.each(data.orgs, function (index, value) {
                        $("#s_orgid").append($("<option>").attr("value", index).append(value))
                    })

                    //попробуем восстановить прежнее значение - если оно есть в текущем списке
                    $("#s_orgid").val(save_id);

                    //contracts_rfr();

                }
            )
        }
    }

    function contracts_rfr() {

        const selector = "#s_contractid";
        var save_contractid = $(selector).val();

        $(selector + " > option").remove();
        //console.log($("#s_contractid >option"));

        $.get("/api/contracts/for_", {
                budget_buildobjid: $("#s_buildobjid").val(),
                budget_orgid: $("#s_orgid").val(),  //ЦФО
            },
            function (data) {

                $.each(data.contracts, function (index, value) {
                    $(selector).append($("<option>").attr("value", index).append(value))
                })

                //попробуем восстановить прежнее значение - если оно есть в текущем списке
                $(selector).val(save_contractid);

                //Если вариант всего один - попробуем сразу его выбрать.
                if ($(selector + ' option').length == 1) {
                    $(selector).prop('selectedIndex', 0);
                    //если выбрали - обновим список видов работ
                    buildopertypeid_rfr();
                }

                //buildopertypeid_rfr();

            }
        )
    }

    function buildopertypeid_rfr() {

        const selector = "#s_buildopertypeid"

        var save_ID = $(selector).val();

        $(selector + " > option").remove();
        //$("#buildopertypeid").css("background-color","#ffe1e1");

        $.get("/api/buildopertypes/for_", {
                buildobjid: $("#s_buildobjid").val(),
                budget_contractid: $("#s_contractid").val(),
            },
            function (data) {
                //console.log(data);

                $("#s_buildopertypeid > option").remove()
                $(selector).append($("<option>"))
                $.each(data.opertypes, function (index, value) {
                    $(selector).append($("<option>").attr("value", index).append(value))
                });

                $(selector).val(save_ID);

                //Если вариант всего один - попробуем сразу его выбрать.
                if ($(selector + ' option').length == 1) {
                    $(selector).prop('selectedIndex', 0);
                }
            }
        )

    }


    $("#s_buildobjid").change(function () {
        orgs_rfr();
    });


    $("#s_orgid").change(function () {
        contracts_rfr();
    });

    $("#s_contractid").change(function () {
        buildopertypeid_rfr();
    });


});
