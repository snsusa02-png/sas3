$(document).ready(function () {

    $("#s_period_type").change(function () {

            var code = $("#s_period_type").val();
            //console.log(code);

            if (code == 1) {
                //day
                $(".dpt_2").hide();
                $(".dpt_3").hide();
                $(".dpt_4").hide();
                $(".dpt_9").hide();

                $(".dpt_1").show();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',false);
                $("#s_begdate").prop('required',true);
                $("#s_enddate").prop('required',false);

                // $("label[for='s_month']").addClass('required');
                // $("label[for='s_year']").addClass('required');
                $("label[for='s_begdate']").html('Дата:');

            } else if (code == 2) {
                //month
                $(".dpt_2").show();
                $(".dpt_3").hide();
                $(".dpt_4").show();
                $(".dpt_9").hide();

                $("#s_month").prop('required',true);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',true);
                $("#s_begdate").prop('required',false);
                $("#s_enddate").prop('required',false);

                // $("label[for='s_month']").addClass('required');
                // $("label[for='s_year']").addClass('required');

            } else if (code == 3) {
                //quarter
                $(".dpt_1").hide();
                $(".dpt_2").hide();
                $(".dpt_3").show();
                $(".dpt_4").show();
                $(".dpt_9").hide();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',true);
                $("#s_year").prop('required',true);
                $("#s_begdate").prop('required',false);
                $("#s_enddate").prop('required',false);

            } else if (code == 4) {
                //year
                $(".dpt_1").hide();
                $(".dpt_2").hide();
                $(".dpt_3").hide();
                $(".dpt_4").show();
                $(".dpt_9").hide();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',true);
                $("#s_begdate").prop('required',false);
                $("#s_enddate").prop('required',false);

            } else if (code == 9) {
                //calendar
                $(".dpt_1").hide();
                $(".dpt_2").hide();
                $(".dpt_3").hide();
                $(".dpt_4").hide();
                $(".dpt_9").show();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',false);
                $("#s_begdate").prop('required',true);
                $("#s_enddate").prop('required',false);

                $("label[for='s_begdate']").html('Начало периода:');

            } else {

                $(".dpt_1").hide();
                $(".dpt_2").hide();
                $(".dpt_3").hide();
                $(".dpt_9").show();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',false);
                $("#s_begdate").prop('required',true);
                $("#s_enddate").prop('required',false);
            }
        }
    );


    if ($(".ac_org_name").length > 0) {

        //Поиск контрагента
        $(".ac_org_name").autocomplete({

            source: function (request, response) {
                //var ft = ($("#sale_dir").val() == -1 || $("#sale_dir").val() == 0) ? 12 : null;
                //var ft = ($("#sale_dir").val() == -1 || $("#sale_dir").val() == 0) ? 12 : null;

                $.ajax({
                    url: "/api/orgs/for_ac",
                    dataType: "json",
                    data: {
                        name: request.term,
                        //name_inn: request.term,
                        //flagtypeid: ft,
                        in_mr_opers_orgid: 1,
                        in_mr_opers_with_suporgid: $("#s_ownorgid").val(),
                        in_mr_opers_orgid_with_wrkdate_ge: $("#s_begdate").val(),
                        in_mr_opers_orgid_with_wrkdate_le: $("#s_enddate").val()
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

                            //var lbl = item.code + " - " + item.name;
                            var lbl = item.name;
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
            minLength: 1,
            autoFill: true,
            cacheLength: 1,
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    var set_id = $(this).parent().find('.ac_id');

                    //$('#set_id').val(ui.item.id);
                    set_id.val(ui.item.id);
                    $(this).val(ui.item.label);

                    let ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");

                    $(this).addClass("ac-act");

                    set_id.change();  //для срабатывания слушателей за изменением этого поля
                }
                event.preventDefault();
            },
            search: function () {
                var set_id = $(this).parent().find('.ac_id');
                set_id.val('');
                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");

                const ac_status = $(this).parent().find('.ac_status');
                ac_status.val("поиск...")
                    .removeClass("ac-fail").removeClass("ac-warn")
                    .addClass("ac-act").show();
            },
            response: function (event, ui) {
                $(this).removeClass("ac-act");
                ac_status = $(this).parent().find('.ac_status');
                if (ui.content.length == 0) {
                    ac_status.val('Варианты не найдены.').removeClass("ac-act").addClass("ac-fail");
                    $(this).addClass("ac-fail");
                } else if (ui.content.length > 15) {
                    ac_status.val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act").addClass("ac-warn");
                } else {
                    //console.log(ui.content);
                    ac_status.hide().val("");
                }
            }
        })
            .on('focus', function (event) {
                $(this).select();
            })
            .on('blur', function (event) {
                ac_id = $(this).parent().find('.ac_id');
                ac_status = $(this).parent().find('.ac_status');
                if ($(this).val().length == 0) {
                    ac_id.val('');
                    $(this).removeClass("ac-act").addClass("ac-fail");

                    ac_status.val('Укажите заказчика!').show()
                        .removeClass("ac-act").addClass("ac-fail");
                } else
                    ac_status.hide().val("");

                ac_id.change();  //для срабатывания слушателей за изменением этого поля
            })
            .data('ui-autocomplete')._renderItem = function (ul, item) {
            //thanks to Salman Arshad for icon and match highlighting code
            //http://salman-w.blogspot.ca/2013/12/jquery-ui-autocomplete-examples.html
            //!подсвечивает только если поиск производится по одному слову.
            var $div = $("<div></div>");
            if (item.icon) {
                $("<img class='m-icon'>").attr("src", "/images/" + item.icon).appendTo($div);
            } else {
                $("<span class='x-icon'></span>").appendTo($div);
            }
            var mName = $("<span class='m-name'></span>").html(item.label).appendTo($div),
                searchText = $.trim(this.term).toLowerCase(),
                currentNode = mName.get(0).firstChild,
                matchIndex, newTextNode, newSpanNode;

            while ((matchIndex = currentNode.data.toLowerCase().indexOf(searchText)) >= 0) {
                newTextNode = currentNode.splitText(matchIndex);
                currentNode = newTextNode.splitText(searchText.length);
                newSpanNode = document.createElement("span");
                newSpanNode.className = "highlight";
                currentNode.parentNode.insertBefore(newSpanNode, currentNode);
                newSpanNode.appendChild(newTextNode);
            }
            return $("<li></li>").append($div).appendTo(ul);
        };
    }


    $("#s_period_type").change();

});
