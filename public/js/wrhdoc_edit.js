$(document).ready(function () {

    $("#doctypeid").change(function () {
        $.get("/stock/wrhdoctypes/params/", {doctypeid: $("#doctypeid").val()},
            function (data) {
                console.log(data);

                $("#sale_dir").val(data.forsale);

                lbl = data.ownorg_label;
                if (lbl == '') lbl = 'Владелец';
                $("#ownorg_label").html(lbl + ':');

                if (data.forstock == "0") {
                    $("#wrh").hide()
                } else {

                    $("#wrh").show()

                    lbl = data.wrh_label;
                    // if (lbl === '') lbl = 'Склад1';
                    if (!lbl) lbl = 'Склад';
                    $("#wrh_label").html(lbl + ':');

                    lbl = data.box_label;
                    if (lbl == '') lbl = 'Отделение';
                    $("#box_label").html(lbl + ':');

                    if (data.need_relwrh == "1") {
                        lbl = data.relwrh_label;
                        if (lbl == '') lbl = 'Связанный склад';
                        $("#relwrh_label").html(lbl + ':');
                        $("#relwrh").show()

                        lbl = data.relbox_label;
                        if (!lbl || lbl == '') lbl = 'Связанное отделение';
                        $("#relbox_label").html(lbl + ':');
                        $("#relbox").show()
                    } else {
                        $("#relwrh").hide();
                        $("#relbox").hide()
                    }
                }

                if (data.need_predoc == "1") {
                    $("#predoc").show()
                } else $("#predoc").hide();

                if (data.need_org == "1") {
                    $("#org").show()
                } else $("#org").hide();

                //временно завяжемся на конкретный тип документа, но нужен спец-флаг wrhdoctypes.dif_saleorg = 1
                if ($("#doctypeid").val() == "3")
                    $("#saleorg").show()
                else $("#saleorg").hide();
            }
        )
    });


    $("#wrhid").change(function () {
        //alert("wrhid = " + $("#wrhid").val());
        if ($("#boxid").length > 0) {
            // alert("boxid = " + $("#boxid").val());
            $("#boxid > option").remove()
            $.get("/api/wrh_boxes/for_", {wrhid: $("#wrhid").val()},
                function (data) {
                    var i = 0;
                    // console.log(data.boxes);
                    // console.log(Object.keys(data.boxes).length); // кол-во отделений

                    $("#boxid > option").remove()
                    $("#boxid").append($("<option>"))   // пустой выбор
                    // console.log('data.boxes.length=' + data.boxes.length);
                    $.each(data.boxes, function (index, value) {
                        i++;
                        //console.log('i=' + i);
                        // $("#boxid").append($("<option>").attr("value", index).attr("selected","selected").append(value))
                        if (i == 1)
                            $("#boxid").append($("<option>").attr("value", index).attr("selected", "selected").append(value))
                        else
                            $("#boxid").append($("<option>").attr("value", index).append(value))
                    });

                    // $("#need_relwrh").val(data.need_relwrh);
                    // $("#need_predoc").val(data.need_predoc);

                    // if (data.need_relwrh == "1") $("#relwrh").show()
                    // else $("#relwrh").hide();
                    //
                    // if (data.need_predoc == "1") $("#predoc").show()
                    // else $("#predoc").hide();
                }
            )
        }
    });

    $("#relwrhid").change(function () {
        //alert($("#wrhid").val());

        $("#relboxid > option").remove()
        $.get("/api/wrh_boxes/for_", {wrhid: $("#relwrhid").val()},
            function (data) {
                //console.log(data);

                $("#relboxid > option").remove()
                $("#relboxid").append($("<option>"))
                $.each(data.boxes, function (index, value) {
                    $("#relboxid").append($("<option>").attr("value", index).append(value))
                });
            }
        )
    });

    if ($(".ac_org_name").length > 0) {

        //Поиск контрагента
        $(".ac_org_name").autocomplete({

            source: function (request, response) {
                //console.log(this.element.attr('data-gk'))
                var ft = (this.element.attr('data-gk') == 1) ? 12 : null;

                $.ajax({
                    //url: "/orgs/autocomplete/search",
                    url: "/api/orgs/for_ac",
                    dataType: "json",
                    data: {
                        name_inn: request.term,
                        flagtypeid: ft,
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

    $('.id_lnk').click(function (e) {
        //переход в элемент справочника
        e.preventDefault();

        //console.log($(this).data('obj'));
        const chkfld_id = $(this).data('id');
        if (chkfld_id) {
            const id = $("#" + chkfld_id).val();
            const ref = $(this).data('obj');
            if (id && ref) {
                var url = "/" + ref + "/" + id + "/edit";
                window.open(url, '_blank');
            }
        }
    });

    //скроем кнопки с классом "hide_chngd" при любом изменении данных в форме
    $(":input").change(function () {
        //triggers change in all input fields including text type
        $(".hide_chngd").hide();
    });

    $(".disp_name").autocomplete({
        source: function (request, response) {
            $.ajax({
                //url: "/users/autocomplete/search",
                url: "/orgstaff/autocomplete/search",
                dataType: "json",
                data: {
                    q: request.term,
                    flagid: 188,    //Признак Диспетчера
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

                        var lbl = item.name;    // + " (" + item.orgname + ": " + item.postname + ")";
                        return {
                            label: lbl,
                            value: item.name,
                            id: item.id
                            //orgid: item.orgid,
                            //orgname: item.orgname
                        }
                    }));
                }
            });
        },
        delay: 250,
        minLength: 2,
        autoFill: true,
        cacheLength: 10,
        autoFocus: true,

        select: function (event, ui) {
            if (ui.item.id) {
                // console.log($(this).parent().find('.userid').val());
                var ac_id = $(this).parent().find('.ac_id');
                ac_id.val(ui.item.id);
                //var orgid = $(this).parent().find('.orgid');
                //orgid.val(ui.item.orgid);
                $(this).val(ui.item.label);

                // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                var ac_status = $(this).parent().find('.ac_status');
                ac_status.hide().removeClass("ac-fail");
                $(this).addClass("ac-act");

                ac_id.change();  //для срабатывания слушателей за изменением этого поля
            }
            event.preventDefault();
        },
        search: function () {
            //$(this).parent().find('.staffid').val('');
            $(this).parent().find('.ac_id').val('');

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

            ac_id = $(this).parent().find('.ac_id');

            if ($(this).val().length == 0) {
                ac_id.val('');

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


});
