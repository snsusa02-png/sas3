$(document).ready(function () {

    $("#initorgname").autocomplete({

        source: function (request, response) {
            $.ajax({
                url: "/orgs/autocomplete/search",
                dataType: "json",
                data: {
                    q: request.term,
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (data) {
                    //console.log(data);
                    response($.map(data, function (item, index) {
                        if (index == 16) {
                            var n = data.length - 16;
                            return {
                                // label: "- Показаны не все варианты (есть еще " + n + " записей), уточните критерий поиска!"
                                label: " ... Показаны не все варианты! Уточните критерий поиска!"
                            }
                        }

                        if (index > 16) return null;

                        var lbl = item.name + " ( ИНН: " + (item.inn ? item.inn : '-') + ", КПП: " + (item.kpp ? item.kpp : '-') + ")";
                        return {
                            label: lbl,
                            value: item.name,
                            id: item.id
                        }
                    }));
                }
            });
        },
        delay: 250,
        minLength: 2,
        autoFill: true,
        cacheLength: 2,
        // autoFocus: true,

        select: function (event, ui) {
            if (ui.item.id) {
                $('#initorgid').val(ui.item.id);
                // $(this).val(ui.item.value);
                $(this).val(ui.item.label);

                // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                $("#ac_orgid").hide().removeClass("ac-fail");
                $(this).addClass("ac-act");

                $("#initorgid").change();  //для срабатывания заполнения договоров
            }
            event.preventDefault();
        },
        search: function () {
            $('#initorgid').val('');
            $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");
            $("#ac_orgid").val("поиск...")
                .removeClass("ac-fail").removeClass("ac-warn")
                .addClass("ac-act").show();
        },
        response: function (event, ui) {
            $(this).removeClass("ac-act");
            if (ui.content.length == 0) {
                $('#ac_orgid').val('Варианты не найдены.')
                    .removeClass("ac-act").addClass("ac-fail");
                $(this).addClass("ac-fail");
            } else if (ui.content.length > 15) {
                $('#ac_orgid').val('Показаны не все варианты! Уточните критерий')
                    .removeClass("ac-act").addClass("ac-warn");
            } else {
                //console.log(ui.content);
                $("#ac_orgid").hide().val("");
            }
        }
    })
        .on('focus', function (event) {
            $(this).select();
        })
        .on('blur', function (event) {
            if ($(this).val().length == 0) {
                $('#initorgid').val('');
                $('#src_orgname').removeClass("ac-act").addClass("ac-fail");
                $('#ac_orgid').val('Укажите организацию!').show()
                    .removeClass("ac-act").addClass("ac-fail");
            } else
                $('#ac_orgid').hide().val("");
        });

});
