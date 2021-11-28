$(document).ready(function () {

    function orgid_rfr() {
        //переформируем список поставщиков

        //alert($("#orgid").val());
        let save_OrgID = 1;//$("#orgid").val();

        $("#orgid > option").remove();
        $("#ownorgid > option").remove();   //зачистим список получателей

        //получим контрагентов-поставщиков - из УПД где есть Получатель = ownorgid
        $.get("/api/orgs/for_", {
                //upd_orgid_by_ownorgid: $("#ownorgid").val(),
                buildobj_sup_not_dlvrd: $("#buildobjid").val(),
                //inv_orgid_by_ownorgid: $("#ownorgid").val(),
                //upd_begdate: $("#begdate").val(),
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#orgid").append($("<option>"))
                $.each(data.orgs, function (index, value) {
                    $("#orgid").append($("<option>").attr("value", index).append(value))
                });

                //попробуем восстановить прежний выбор
                $("#orgid").val(save_OrgID);

                //обновим связанные элементы
                ownorgid_rfr();
            }
        )


    }

    function ownorgid_rfr() {

        //alert($("#orgid").val());
        let save_id = $("#ownorgid").val();

        $("#ownorgid > option").remove();

        //получим контрагентов-поставщиков - из УПД где есть Получатель = ownorgid
        $.get("/api/orgs/for_", {
                buildobj_ownorg_not_dlvrd: $("#buildobjid").val(),
                org_ownorg_not_dlvrd: $("#orgid").val(),
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#ownorgid").append($("<option>"))
                $.each(data.orgs, function (index, value) {
                    $("#ownorgid").append($("<option>").attr("value", index).append(value))
                });

                //попробуем восстановить прежний выбор
                $("#ownorgid").val(save_id);

                //обновим связанные элементы
                //buildobjid_rfr(save_buildopertypeid);
            }
        )


    }

    function wrhs_rfr() {

        //alert($("#orgid").val());
        let selector = "#tgt_wrhid";
        let save_id = $(selector).val();

        $(selector + " > option").remove();

        //получим контрагентов-поставщиков - из УПД где есть Получатель = ownorgid
        $.get("/api/wrhs/for_", {
                buildobjid: $("#buildobjid").val(),
                active: 1,
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $(selector).append($("<option>"))
                $.each(data.wrhs, function (index, value) {
                    $(selector).append($("<option>").attr("value", index).append(value))
                });

                //попробуем восстановить прежний выбор
                $(selector).val(save_id);

                //Если вариант всего один - попробуем сразу его выбрать. 2 - потому что есть еще placeholder
                if ($(selector+' option').length == 2) {
                    $(selector).prop('selectedIndex', 1);
                }
                //обновим связанные элементы
                //buildobjid_rfr(save_buildopertypeid);
            }
        )


    }

    $("#buildobjid").change(function () {
        //alert($("#buildobjid").val())
        //при изменении объекта переформируем список поставщиков
        orgid_rfr();
        wrhs_rfr();
    });

    $("#orgid").change(function () {
        //при изменении поставщика переформируем список получателей
        ownorgid_rfr();
    });

    // $(".stfname").change(function (e) {
    //     console.log($(this).val());
    // });

    $(".dlvrd_qty").change(function () {
        var q = (parseFloat($(this).val()) * parseFloat($(this).attr('k_unit'))).toFixed(3);
        $(this).parent().parent().find('.supunit_qty').html(q);
    });


    if ($(".stfname").length > 0) {

        $(".stfname").autocomplete({

            source: function (request, response) {
                $.ajax({
                    url: "/orgstaff/autocomplete/search",
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

                            var lbl = item.name + " (" + item.orgname + ": " + item.postname + ")";
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                                orgid: item.orgid,
                                orgname: item.orgname
                            }
                        }));
                    }
                });
            },
            delay: 250,
            minLength: 2,
            autoFill: true,
            cacheLength: 10,
            // autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    // console.log($(this).parent().find('.staffid').val());
                    var staffid = $(this).parent().find('.staffid');
                    staffid.val(ui.item.id);
                    $(this).val(ui.item.label);

                    var orgid = $(this).parent().find('.orgid');
                    orgid.val(ui.item.orgid);


                    // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");
                }
                event.preventDefault();
            },
            search: function () {
                $(this).parent().find('.staffid').val('');

                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");

                var ac_status = $(this).parent().find('.ac_status');
                ac_status.val("поиск...")
                    .removeClass("ac-fail")
                    .removeClass("ac-warn")
                    .addClass("ac-act")
                    .show();
            },
            response: function (event, ui) {
                var ac_status = $(this).parent().find('.ac_status');
                $(this).removeClass("ac-act");
                if (ui.content.length == 0) {
                    ac_status.val("Варианты не найдены.")
                        .removeClass("ac-act")
                        .addClass("ac-fail");
                    $(this).addClass("ac-fail");
                } else if (ui.content.length > 15) {
                    ac_status.val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act")
                        .addClass("ac-warn");
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
                if ($(this).val().length == 0) {
                    $(this).parent().find('.staffid').val('');

                    $(this).parent().find('.stfname')
                        .removeClass("ac-act")
                        .addClass("ac-fail");

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.val('Укажите сотрудника!')
                        .show()
                        .removeClass("ac-act")
                        .addClass("ac-fail");
                } else
                    $(this).parent().find('.ac_status').hide().val("");
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
        }
    }


});
