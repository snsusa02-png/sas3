$(document).ready(function () {

    function buildobjid_rfr() {
        var save_ID = $("#s_buildopertypeid").val();
        console.log('s_buildopertypeid=' + save_ID);
        $("#s_buildopertypeid > option").remove();
        //$("#buildopertypeid").css("background-color","#ffe1e1");

        $.get("/api/buildopertypes/m15", {
                srcorgid: $("#s_ownorgid").val(),
                tgtorgid: $("#s_orgid").val(),
                buildobjid: $("#s_buildobjid").val()
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#s_buildopertypeid > option").remove()
                $("#s_buildopertypeid").append($("<option>"))
                $.each(data.buildopertypes, function (index, value) {
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

    $("#s_ownorgid").change(function () {

        //alert($("#s_orgid").val());
        var save_ID = $("#s_orgid").val();
        //console.log('s_buildobjid=' + save_ID);


        $("#s_orgid > option").remove();
        $("#s_buildobjid > option").remove();
        $("#s_buildopertypeid > option").remove();
        //$("#buildopertypeid").css("background-color","#ffe1e1");

        //получим объекты, участвующие в заявках где есть передача давальческих материалов между s_ownorgid и s_orgid
        $.get("/api/orgs/m15_tgt", {
                srcorgid: $("#s_ownorgid").val(),
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#s_orgid > option").remove()
                $("#s_orgid").append($("<option>"))
                $.each(data.orgs, function (index, value) {
                    $("#s_orgid").append($("<option>").attr("value", index).append(value))
                });
            }
        )

        $("#s_orgid").val(save_ID);
    });

    $("#s_orgid").change(function () {

        //alert($("#s_orgid").val());
        var save_ID = $("#s_buildobjid").val();
        //console.log('s_buildobjid=' + save_ID);

        $("#s_buildobjid > option").remove();
        $("#s_buildobjid > option").remove();
        $("#s_buildopertypeid > option").remove();

        //$("#buildopertypeid").css("background-color","#ffe1e1");

        //получим объекты, участвующие в заявках где есть передача давальческих материалов между s_ownorgid и s_orgid
        $.get("/buildobjs/m15_src_tgt/params", {
                srcorgid: $("#s_ownorgid").val(),
                tgtorgid: $("#s_orgid").val(),
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#s_buildobjid > option").remove()
                $("#s_buildobjid").append($("<option>"))
                $.each(data.buildobjs, function (index, value) {
                    $("#s_buildobjid").append($("<option>").attr("value", index).append(value))
                });
            }
        )

        $("#s_buildobjid").val(save_ID);
    });


});
