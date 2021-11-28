$(document).ready(function () {

    $("#btn1").click(function (e) {
        $('#main_body').toggle(400);
        $('#main_ftr').toggle(400);
    });

    $("#projid").change(function () {
        //alert($("#projid").val());
        $("#buildobjid > option").remove()
        $.get("/projects/buildobjs/params", {projectid: $("#projid").val()},
            function (data) {
                console.log(data);
                //$("#tgt_addr").val(data.address);

                $("#buildobjid > option").remove()
                $("#buildobjid").append($("<option>"))
                $.each(data.buildobjs, function (index, value) {
                    $("#buildobjid").append($("<option>").attr("value", index).append(value))
                });
            }
        )
    });


    $("#meet_begdt").change(function () {
        var begdt = moment($("#meet_begdt").val());
        var enddt = begdt.add(0.5, 'hours');

        $("#meet_enddt").val(enddt.format('YYYY-MM-DD[T]HH:mm'));

        // console.log(moment.duration(fctenddt.diff(fctbegdt, 'hours', true)));
        //console.log(fctenddt.diff(fctbegdt, 'hours', true));
        //console.log(moment.utc(moment.duration(fctenddt) - moment.duration(fctbegdt)).format('HH:mm'));
    });

    function chk_accept_decision() {
        var dcsn = $("#accept_decision").val();
        if (!dcsn || dcsn == 1)
            $("#freetime").hide();
        else
            $("#freetime").show();

    }

    $("#accept_decision").change(function () {
        chk_accept_decision();
        if ($("#dcsn_at").val() != '') $("#btn_decision").html("Изменить решение")
            .removeClass('btn-primary').addClass('btn-danger');
        $("#btn_decision").show();
    });
    $("#dcsn_descript").change(function () {
        if ($("#dcsn_at").val() != '') $("#btn_decision").html("Изменить решение")
            .removeClass('btn-primary').addClass('btn-danger');
        $("#btn_decision").show();
    });

    $("#free_begdt").change(function () {
        var begdt = moment($("#free_begdt").val());
        var enddt = begdt;//.add(0.5, 'hours');

        $("#free_enddt").val(enddt.format('YYYY-MM-DD[T]HH:mm'));
    });


    chk_accept_decision();

});
