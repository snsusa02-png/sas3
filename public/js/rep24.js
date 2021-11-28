$(document).ready(function () {

    function ownorgid_rfr() {

        //alert($("#s_orgid").val());
        var save_ID = $("#s_orgid").val();
        var save_buildobjid = $("#s_buildobjid").val();
        var save_buildopertypeid = $("#s_buildopertypeid").val();
        //console.log('s_orgid=' + save_ID);
        //console.log('save_buildopertypeid=' + save_buildopertypeid);


        //$("#s_orgid > option").remove();
        $("#s_buildobjid > option").remove();
        $("#s_buildopertypeid > option").remove();
        //$("#buildopertypeid").css("background-color","#ffe1e1");

        //получим объекты, связанные с УПД где есть Получатель = s_ownorgid
        $.get("/api/buildobjs/for_", {
                upd_ownorgid: $("#s_ownorgid").val(),
                upd_begdate: $("#s_begdate").val(),
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#s_buildobjid").append($("<option>"))
                $.each(data.buildobjs, function (index, value) {
                    $("#s_buildobjid").append($("<option>").attr("value", index).append(value))
                });


                $("#s_buildobjid").val(save_buildobjid);

                buildobjid_rfr(save_buildopertypeid);
            }
        )


    }

    $("#s_ownorgid").change(function () {
        ownorgid_rfr();
    });

    function buildobjid_rfr(save_buildopertypeid) {

        //обновим зависимые списки
        //var save_ID = $("#s_buildopertypeid").val()
        var save_ID = (save_buildopertypeid) ? save_buildopertypeid : $("#s_buildopertypeid").val();

        //console.log('s_buildopertypeid= save_id=' + save_ID);

        $("#s_buildopertypeid > option").remove();
        $("#s_contractid > option").remove();
        //$("#buildopertypeid").css("background-color","#ffe1e1");

        $.get("/api/buildopertypes/for_", {
                buildobjid: $("#s_buildobjid").val(),
                upd_ownorgid: $("#s_ownorgid").val(),
                upd_begdate: $("#s_begdate").val(),
                upd_enddate: $("#s_enddate").val(),
                //at_upd: 1,
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#s_buildopertypeid > option").remove()
                $("#s_buildopertypeid").append($("<option>"))
                $.each(data.opertypes, function (index, value) {
                    $("#s_buildopertypeid").append($("<option>").attr("value", index).append(value))
                });

                $("#s_buildopertypeid").val(save_ID);

                //-----------------------------------
                contracts_rfr();
                //-----------------------------------
            }
        )

    }

    function contracts_rfr() {

        var save_contractid = $("#s_contractid").val();

        $("#s_contractid > option").remove();
        //console.log($("#s_contractid >option"));

        $.get("/api/contracts/for_", {
                in_equiprsts: 1,
                orgid: $("#s_ownorgid").val(),  //получатель УПД
                buildobjid: $("#s_buildobjid").val(),
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#s_contractid").append($("<option>"))
                $.each(data.contracts, function (index, value) {
                    $("#s_contractid").append($("<option>").attr("value", index).append(value))
                })


                //попробуем восстановить прежнее значение - если оно есть в текущем списке
                $("#s_contractid").val(save_contractid);

                            }
        )


    }


    $("#s_buildobjid").change(function () {
        //alert($("#s_buildobjid").val());
        buildobjid_rfr();

        contracts_rfr();
    });


    // при открытии ------
    //ownorgid_rfr();
});
