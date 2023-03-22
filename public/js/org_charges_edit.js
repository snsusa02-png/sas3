$(document).ready(function () {

    function rfr_depid_lnk() {
        if ($("#depid").val())
            $("#depid_lnk").show()
        else
            $("#depid_lnk").hide()

    }

    function chk_strict_dep() {
        //Если вариант в списке вариантов всего один,
        // то => подразделения не определены => скрываем список, открываем текстовое поле
        if ($('#depid option').length == 1) {

            $("#depid").hide()
            $("#depid_lnk").hide()
            $("#postid").hide()
            $("#postid_lnk").hide()
            $("#stdpostunit_div").hide()
            $("#outofoffice_div").hide()

            $("#depname").show()
            $("#postname").show()

        } else {
            //Подразделения и должности задаются из справочников

            $("#depid").show()
            $("#postid").show()
            $("#stdpostunit_div").show()
            $("#outofoffice_div").show()

            $("#depname").hide()
            $("#postname").hide()

            rfr_depid_lnk()
        }
    }

    $("#orgid").change(function () {
        //alert($("#orgid").val())
        $("#depid > option").remove()
        $.get("/api/orgdeps/for_", {
                orgid: $("#orgid").val(),
                with_post_vacancies_staff: $("#id").val(),
                active_or_current: $("#depid").val(),
            },
            function (data) {
                //console.log(data);
                //$("#tgt_addr").val(data.address);

                $("#depid").append($("<option>"))
                $.each(data.orgdeps, function (index, value) {
                    $("#depid").append($("<option>").attr("value", index).append(value))
                });
                //console.log($('#depid option').length);

                chk_strict_dep();
            }
        )


    });

    $("#depid").change(function () {
        //alert($("#depid").val());

        $("#postid > option").remove()
        $.get("/api/orgposts/dep_posts", {depid: $("#depid").val(), staffid: $("#id").val(),},
            function (data) {
                //console.log(data);
                //$("#tgt_addr").val(data.address);

                $("#postid").append($("<option>"))
                $.each(data.list, function (index, value) {
                    $("#postid").append($("<option>").attr("value", index).append(value))
                });

            }
        )

        rfr_depid_lnk()
    });


    $("#postid").change(function () {
        //alert($("#postid").val());

        $.get("/api/orgposts/stdlimunits", {postid: $("#postid").val(), staffid: $("#id").val()},
            function (data) {
                //console.log(data);
                var max = parseFloat(data);
                max = (max > 1.5) ? 1.5 : max;
                $("#stdpostunit").attr('max', max);
                if ($("#stdpostunit").val() > max)
                    $("#stdpostunit").val(max);
                else if ($("#stdpostunit").val() === '')
                    $("#stdpostunit").val((max >= 1) ? 1 : max);
            }
        )
    });


    $("#sex").change(function () {

        //alert($("#sex").val())

        var items = ($("#sex").val() == 'F') ? {0: 'не замужем', 1: 'замужем'} : {0: 'не женат', 1: 'женат'}

        //сохраним текущий статус
        let pre_val = $("#marriage").val()
        $("#marriage > option").remove()

        $("#marriage").append($("<option>"))
        $.each(items, function (index, value) {
            $("#marriage").append($("<option>").attr("value", index).append(value))
        });

        //восстановим статус
        $("#marriage").val(pre_val)

    });


    //при открытии
    chk_strict_dep();

    $('#depid_lnk').click(function (e) {
        //console.log($(this).attr('id'));
        e.preventDefault();
        if ($("#depid").val()) {
            var url = '/orgdeps/' + $("#depid").val() + '/edit';
            window.open(url, '_blank');
        }
    });

    $('#postid_lnk').click(function (e) {
        //console.log($(this).attr('id'));
        e.preventDefault();
        if ($("#postid").val()) {
            var url = '/orgposts/' + $("#postid").val() + '/edit';
            window.open(url, '_blank');
        }
    });
});
