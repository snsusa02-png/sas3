$(document).ready(function () {

    $("#opertypeid").focus(function () {
        $(this).data('val', $(this).val());
    });

    $("#opertypeid").change(function () {

        const opertypeid = $(this).val();
        const pre_opertypeid = $(this).data("val");

        $(".ots_" + opertypeid).show()
        $(".oth_" + pre_opertypeid).show()
        $(".oth_" + opertypeid).hide()

        //alert(pre_opertypeid+' '+opertypeid);
    });

    $("#raid_qty, #raid_salary").change(function () {

        const raid_qty = parseFloat($("#raid_qty").val());
        const raid_salary = parseFloat($("#raid_salary").val());
        const salary = Math.round(raid_qty * raid_salary * 100) / 100;

        $("#salary").val(salary)
    });

    function recalc_hrs() {

        var day_brkhrs = parseFloat($("#day_brkhrs").val());
        var night_brkhrs = parseFloat($("#night_brkhrs").val());
        day_brkhrs = isNaN(day_brkhrs) ? 0 : day_brkhrs;
        night_brkhrs = isNaN(night_brkhrs) ? 0 : night_brkhrs;

        var begdt = moment($("#wrkdate").val() + ' ' + $("#begtime").val());
        var enddt = moment($("#wrkenddate").val() + ' ' + $("#endtime").val());
        //console.log(begdt, enddt, (enddt<begdt), (enddt>=begdt));
        //console.log(begdt.format('DD.MM.yyyy HH:mm'), enddt.format('DD.MM.yyyy HH:mm'));
        //console.log(begdt, enddt);

        //console.log($("#wrkenddate").val())
        if ($("#wrkdate").val() != '')
            $("#wrkenddate").attr('min', $("#wrkdate").val());
        if ($("#wrkenddate").val() == '')
            $("#wrkenddate").val($("#wrkdate").val());
        if (1 == 0 && enddt < begdt) {
            $("#wrkenddate").val($("#wrkdate").val());
            $("#endtime").val($("#begtime").val());
            var enddt = moment($("#wrkenddate").val() + ' ' + $("#endtime").val());
        }

        // var fctenddt = begdt.add(3, 'hours');
        // console.log(fctenddt);
        //console.log(enddt.diff(begdt, 'hours', true));
        //var stfwrkhrs = Math.round((enddt.diff(begdt, 'hours', true) - day_brkhrs - night_brkhrs) * 10) / 10;
        var stfwrkhrs = Math.round((enddt.diff(begdt, 'hours', true)) * 10) / 10;
        // $("#mchnwrkhrs").val(stfwrkhrs);
        if (isNaN(stfwrkhrs))
            $("#stfwrkhrs").val('-');
        else
            $("#stfwrkhrs").val(stfwrkhrs);
        //console.log(stfwrkhrs)

        var day_hrs = 0;
        var night_hrs = 0;
        var hr_duration = 0;
        var pre_dt = moment(begdt);
        var cur_dt = moment(begdt);
        var add_minutes = 60 - begdt.format('mm');
        //console.log(add_minutes);
        while (cur_dt < enddt) {
            //cur_dt = cur_dt.add(1, 'hour');
            cur_dt = cur_dt.add(add_minutes, 'minute');
            if (cur_dt > enddt)
                cur_dt = moment(enddt);

            //console.log(pre_dt.format('HH:mm'), cur_dt.format('HH:mm'));
            hr_duration = moment.duration(cur_dt.diff(pre_dt)).asHours();
            if (pre_dt.format('HH:mm') >= '07:00' && pre_dt.format('HH:mm') <= '20:00'
                && cur_dt.format('HH:mm') >= '07:00' && cur_dt.format('HH:mm') <= '20:00') {
                // День
                day_hrs += hr_duration;
            } else {
                night_hrs += hr_duration;
            }

            pre_dt = moment(cur_dt);
            add_minutes = 60;
        }
        //console.log(day_hrs, night_hrs)
        $("#day_hrs").val(Math.round(day_hrs * 10) / 10);
        $("#night_hrs").val(Math.round(night_hrs * 10) / 10);
        $("#day_wrkhrs").val(Math.round(day_hrs - Math.min(day_brkhrs, day_hrs)));
        $("#night_wrkhrs").val(Math.round(night_hrs - Math.min(night_brkhrs, night_hrs)));

    }

    $("#wrkdate, #begtime, #wrkenddate, #endtime, #day_brkhrs, #night_brkhrs").change(function () {
        recalc_hrs();
    });

    $("#qty_unittypeid").change(function () {
        //$(this).find('option:selected').text();
        $("#unload_qty_unit").html($(this).find('option:selected').text());
    });

    $("#load_price").change(function () {
        //console.log(this.value)
        $("#unload_price").val(this.value)
        recalc_unloadsum()
    });

    $("#orgid").change(function () {
        $("#unload_placeid").val('')
        $("#unload_placename").val('')
    });


    function recalc_fuel() {
        const begqty = parseFloat($("#fuel_begqty").val());
        const inpqty = parseFloat($("#fuel_inpqty").val());
        const endqty = parseFloat($("#fuel_endqty").val());

        var spentqty = '-';
        if (!(isNaN(begqty) || isNaN(inpqty) || isNaN(endqty))) {
            spentqty = begqty + inpqty - endqty
        }
        $("#fuel_spentqty").val(spentqty);
        //console.log(spentqty)
    }

    $("#fuel_begqty, #fuel_inpqty, #fuel_endqty").change(function () {
        recalc_fuel();
    });

    function recalc_meter() {
        const begqty = parseFloat($("#meter_begqty").val());
        const endqty = parseFloat($("#meter_endqty").val());

        var qty = '-';
        if (!(isNaN(begqty) || isNaN(endqty))) {
            qty = endqty - begqty
        }
        $("#meter_qty").val(qty);
        //console.log(qty)
    }

    $("#meter_begqty, #meter_endqty").change(function () {
        recalc_meter();
    });


    function recalc_loadsum() {
        const qty = parseFloat($("#load_qty").val()) ?? 0;
        const price = parseFloat($("#load_price").val()) ?? 0;

        const sum = Math.round(100 * qty * price) / 100;
        $("#load_sum").val(sum);
        //console.log(sum)

        //установить min для ownorg_sum
        $("#ownorg_sum").attr('min', sum);
        if ($("#ownorg_sum").val() < sum)
            $("#ownorg_sum").val(sum)
    }

    $("#load_qty, #load_price").change(function () {
        recalc_loadsum();
    });

    $("#load_qty").change(function () {
        $("#unload_qty").val($(this).val())
        recalc_unloadsum()
    });


    function recalc_unloadsum() {
        const qty = parseFloat($("#unload_qty").val()) ?? 0;
        const price = parseFloat($("#unload_price").val()) ?? 0;

        const sum = Math.round(100 * qty * price) / 100;
        $("#unload_sum").val(sum);
        //console.log(sum)

        //установить max для ownorg_sum
        $("#ownorg_sum").attr('max', sum);
        if ($("#ownorg_sum").val() > sum)
            $("#ownorg_sum").val(sum)

    }

    $("#unload_qty, #unload_price").change(function () {
        recalc_unloadsum();
    });


    //на изменение ID заполняемого по автокомплиту
    $(".ac_id").change(function () {
        //отработаем скрытие/открытие кнопки со ссылкой на выбраный элмент спр-ка в зависимости от наличия значения в id
        if ($(this).val()) {
            $(this).parent().find('.id_lnk').hide()
            const id_lnk = $(this).parent().find('.id_lnk');
            if (id_lnk && id_lnk.data('id') && id_lnk.data('obj'))
                $(this).parent().find('.id_lnk').show() //отобразить ссылку на карточку редактирования объекта справочника
        } else {
            $(this).parent().find('.id_lnk').hide()
        }
    });


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


    $("#load_ownorgid, #unload_ownorgid").change(function () {

        const id1 = $("#load_ownorgid").val();
        const id2 = $("#unload_ownorgid").val();

        if (id1 && id2 && id1 != id2) {
            //show
            $(".ownorg_transfer").show()
        } else {
            //hide
            $(".ownorg_transfer").hide()
        }
    });


    if ($(".driver_name").length > 0) {
        $(".driver_name").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/orgstaff/autocomplete/search",
                    dataType: "json",
                    data: {
                        q: request.term,
                        flagid: 187,    //признак водителя
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
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    // console.log($(this).parent().find('.staffid').val());
                    var ac_id = $(this).parent().find('.ac_id');
                    ac_id.val(ui.item.id);
                    // var orgid = $(this).parent().find('.orgid');
                    // orgid.val(ui.item.orgid);
                    $(this).val(ui.item.label);

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
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
                ac_id = $(this).parent().find('.staffid');
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
    }

    if ($(".machine_name").length > 0) {
        $(".machine_name").autocomplete({
            source: function (request, response) {

                $.ajax({
                    url: "/api/machines/for_ac",
                    dataType: "json",
                    data: {
                        //q: request.term,
                        s_name: request.term,
                        opertypeid: $("#opertypeid").val(),
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

                            var lbl = item.name + " (" + item.regnum + ". " + item.orgname + ")";
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                                orgid: item.orgid,
                                orgname: item.orgname,
                                in_gk: item.in_gk
                            }
                        }));
                    }
                });
            },
            delay: 250,
            minLength: 1,
            autoFill: true,
            cacheLength: 10,
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    var ac_id = $(this).parent().find('.ac_id');
                    ac_id.val(ui.item.id);

                    var orgid = $(this).parent().find('.orgid');
                    orgid.val(ui.item.orgid);
                    $(this).val(ui.item.label);

                    // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    //console.log(ui.item.in_gk);
                    $("#in_gk").val(ui.item.in_gk);
                    //if (ui.item.in_gk == 1) {
                    if ($("#in_gk").val() == 1) {
                        $(".driver_info").show();
                        $(".salary_info").show();
                        $("#driverid").val('');     //зачищаем 160

                    } else {
                        $(".driver_info").hide();
                        $(".salary_info").hide();
                        $("#driverid").val(160);    //160 - "-неизвестный--водитель-"
                        $("#raid_salary").val(0);
                    }

                    //получить режимы эксплуатации -----
                    var selector = "#mot_id";
                    var save_ID = $(selector).val();
                    //console.log(save_ID);
                    $(selector + " > option").remove()
                    $.get("/api/mchn_opertypes/for_", {machineid: ac_id.val()},
                        function (data) {
                            //console.log(data);
                            $(selector).append($("<option>"))
                            $.each(data.list, function (index, value) {
                                $(selector).append($("<option>").attr("value", index).append(value))
                            });
                            //Если вариант всего один - попробуем сразу его выбрать. 2 - потому что есть еще placeholder
                            if ($(selector + ' option').length == 2) {
                                $(selector).prop('selectedIndex', 1);
                            }

                            $(selector).val(save_ID);
                        }
                    )

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля

                }
                event.preventDefault();
            },
            search: function () {
                $(this).parent().find('.machineid').val('');

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

                    $(this).parent().find('.ac_name')
                        .removeClass("ac-act")
                        .addClass("ac-fail");

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.val('Укажите рег. номер техники!')
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
    }


    if ($(".ac_suporg_name").length > 0) {

        //Поиск контрагента
        $(".ac_suporg_name").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/api/orgs/for_ac",
                    dataType: "json",
                    data: {
                        name_inn: request.term,
                        //flagtypeid: 13, //13 - признак поставщика
                        active: 1,
                        in_ri_sup_prices: 1,    //есть записи о товарах/ценах
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

                            //var lbl = item.code  + " - " +item.name;
                            var lbl = item.name + " (" + (item.inn ?? '-') + ")";
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
                    .removeClass("ac-fail")
                    .removeClass("ac-warn")
                    .addClass("ac-act")
                    .show();
            },
            response: function (event, ui) {
                $(this).removeClass("ac-act");
                ac_status = $(this).parent().find('.ac_status');
                if (ui.content.length == 0) {
                    ac_status.val('Варианты не найдены.')
                        .removeClass("ac-act")
                        .addClass("ac-fail")
                        .attr('title', 'Укажите поставщика!');
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

                    ac_status.val('Укажите поставщика!')
                        .attr('title', 'Укажите поставщика!')
                        .show()
                        .removeClass("ac-act").addClass("ac-fail").hide(1000);
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

    if ($(".ac_org_name").length > 0) {

        //Поиск контрагента
        $(".ac_org_name").autocomplete({
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

    if ($("#load_cargo_name").length > 0) {
        $("#load_cargo_name").autocomplete({
            source: function (request, response) {
                $.ajax({
                    //url: "/refitems/autocomplete/search",
                    url: "/api/refitems/for_ac",
                    dataType: "json",
                    data: {
                        name: request.term,
                        suporgid: $("#suporgid").val(),
                        load_placeid: $("#load_placeid").val(),
                        price_on_date: $("#wrkdate").val(),
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        //
                        // console.log(n);
                        response($.map(data, function (item, index) {
                            if (index == 16) {
                                var n = data.length - 16;
                                return {
                                    // label: "- Показаны не все варианты (есть еще " + n + " записей), уточните критерий поиска!"
                                    label: " ... Показаны не все варианты! Уточните критерий поиска!"
                                }
                            }

                            if (index > 16) return null;

                            //var lbl = item.code + " | " + item.name;
                            var lbl = item.name;
                            //lbl = lbl + " (категория: " + item.itname;
                            //if (item.specinfo) lbl = lbl + "; " + item.specinfo;
                            lbl = lbl + "(цена: " + item.price + " &#x20bd; / " + item.unit + ")";
                            return {
                                label: lbl,
                                value: item.name,
                                price: item.price,
                                code1s: item.code,
                                icon: item.photourl,
                                specinfo: item.specinfo,
                                unit: item.unit,
                                unittypeid: item.unittypeid,
                                id: item.id
                            }
                        }));
                    }
                });
            },
            delay: 300,
            minLength: 2,
            autoFill: true,
            cacheLength: 1,
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    ac_id = $(this).parent().find('.ac_id')
                    ac_id.val(ui.item.id);

                    $('#load_price').val(ui.item.price).prop('readonly', true);

                    //установим допустимую ЕИ ---------------------------------------------
                    //$('#qty_unittypeid').val(ui.item.unittypeid).prop('readonly', true);
                    $("#qty_unittypeid > option").remove();
                    $("#qty_unittypeid").append($("<option>").attr("value", ui.item.unittypeid).append(ui.item.unit))
                    $("#load_qty_unit").html(ui.item.unit)
                    //$("#unload_qty_unit").html(ui.item.unit)
                    //---------------------------------------------------------------------

                    //$('#code1s').val(ui.item.code1s);
                    //$('#code').val(ui.item.id);
                    //$('#unit').html(ui.item.unit);
                    //$('#unit_html').html(ui.item.unit);
                    //$('#specinfo').html(ui.item.specinfo);

                    $(this).val(ui.item.value);

                    ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().val("").removeClass("ac-fail");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                    $('#load_price').change();

                } else
                    event.preventDefault();
            },
            search: function () {
                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");
                ac_status = $(this).parent().find('.ac_status');
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

                    $('#load_price').prop('readonly', false);

                } else if (ui.content.length > 15) {
                    ac_status.val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act").addClass("ac-fail");
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
                console.log($(this).val().length);

                if ($(this).val().length == 0) {
                    ac_id.val('');
                    $(this).removeClass("ac-act").addClass("ac-fail");

                    ac_status.val('Укажите товар!').show()
                        .removeClass("ac-act").addClass("ac-fail");

                    //specifics -----------------
                    $('#load_price').prop('readonly', false);
                    $('#unittypeid').prop('readonly', false);

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

    if ($("#unload_cargo_name").length > 0) {

        $("#unload_cargo_name").autocomplete({
            source: function (request, response) {
                $.ajax({
                    //url: "/refitems/autocomplete/search",
                    url: "/api/refitems/for_ac",
                    dataType: "json",
                    data: {
                        name: request.term,
                        // suporgid: $("#suporgid").val(),
                        // load_placeid: $("#load_placeid").val(),
                        // price_on_date: $("#wrkdate").val(),
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        //
                        // console.log(n);
                        response($.map(data, function (item, index) {
                            if (index == 16) {
                                var n = data.length - 16;
                                return {
                                    // label: "- Показаны не все варианты (есть еще " + n + " записей), уточните критерий поиска!"
                                    label: " ... Показаны не все варианты! Уточните критерий поиска!"
                                }
                            }

                            if (index > 16) return null;

                            //var lbl = item.code + " | " + item.name;
                            var lbl = item.name;
                            //lbl = lbl + " (категория: " + item.itname;
                            //if (item.specinfo) lbl = lbl + "; " + item.specinfo;
                            lbl = lbl + "(цена: " + item.price + " &#x20bd; / " + item.unit + ")";
                            return {
                                label: lbl,
                                value: item.name,
                                price: item.price,
                                code1s: item.code,
                                icon: item.photourl,
                                specinfo: item.specinfo,
                                unit: item.unit,
                                unittypeid: item.unittypeid,
                                id: item.id
                            }
                        }));
                    }
                });
            },
            delay: 300,
            minLength: 2,
            autoFill: true,
            cacheLength: 1,
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    ac_id = $(this).parent().find('.ac_id')
                    ac_id.val(ui.item.id);

                    $('#unload_price').val(ui.item.price)   //.prop('readonly', true);

                    //установим допустимую ЕИ ---------------------------------------------
                    //$("#qty_unittypeid > option").remove();
                    //$("#qty_unittypeid").append($("<option>").attr("value", ui.item.unittypeid).append(ui.item.unit))
                    $("#unload_qty_unit").html(ui.item.unit)
                    //---------------------------------------------------------------------

                    //$('#code1s').val(ui.item.code1s);
                    //$('#code').val(ui.item.id);
                    //$('#unit').html(ui.item.unit);
                    //$('#unit_html').html(ui.item.unit);
                    //$('#specinfo').html(ui.item.specinfo);

                    $(this).val(ui.item.value);

                    ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().val("").removeClass("ac-fail");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                    $('#unload_price').change();

                } else
                    event.preventDefault();
            },
            search: function () {
                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");
                ac_status = $(this).parent().find('.ac_status');
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

                    $('#load_price').prop('readonly', false);

                } else if (ui.content.length > 15) {
                    ac_status.val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act").addClass("ac-fail");
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
                console.log($(this).val().length);

                if ($(this).val().length == 0) {
                    ac_id.val('');
                    $(this).removeClass("ac-act").addClass("ac-fail");

                    ac_status.val('Укажите товар!').show()
                        .removeClass("ac-act").addClass("ac-fail");

                    //specifics -----------------
                    $('#load_price').prop('readonly', false);
                    $('#unittypeid').prop('readonly', false);

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

    if ($(".load_placename").length > 0) {
        $(".load_placename").autocomplete({
            source: function (request, response) {

                $.ajax({
                    //url: "/api/places/for_ac",
                    url: "/api/org_places/for_ac",
                    dataType: "json",
                    data: {
                        name_address: request.term,
                        orgid: $("#suporid").val(),
                        placetypeid: 2,
                        active: 1,
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

                            //var lbl = item.name + " (" + item.code + ")";

                            //var lbl = item.name + " (" + (item.address ?? '-') + ")";
                            var lbl = item.name;
                            if (item.address)
                                lbl += " (" + item.address + ")";

                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                                //orgid: item.orgid,
                                //orgname: item.orgname
                            }
                        }));
                    }
                });
            },
            delay: 250,
            minLength: 1,
            autoFill: true,
            cacheLength: 10,
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    const ac_id = $(this).parent().find('.ac_id');
                    ac_id.val(ui.item.id);
                    $(this).val(ui.item.label);

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                }
                event.preventDefault();
            },
            search: function () {
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

                    //$(this).parent().find('.machine_name')
                    $(this).removeClass("ac-act").addClass("ac-fail");

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.val('Укажите рег. номер техники!')
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
    }

    if ($("#unload_placename").length > 0) {
        $(".unload_placename").autocomplete({
            source: function (request, response) {

                $.ajax({
                    //url: "/api/places/for_ac",
                    url: "/api/org_places/for_ac",
                    dataType: "json",
                    data: {
                        s_name: request.term,
                        orgid: $("#orgid").val(),
                        s_for_unload: 1,
                        s_active: 1,
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

                            var lbl = item.name;
                            if (item.address)
                                lbl += " (" + item.address + ")";

                            //var lbl = item.name;
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                            }
                        }));
                    }
                });
            },
            delay: 250,
            minLength: 1,
            autoFill: true,
            cacheLength: 10,
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    $(this).val(ui.item.label);
                    const ac_id = $(this).parent().find('.ac_id');
                    ac_id.val(ui.item.id);

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                }
                event.preventDefault();
            },
            search: function () {
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
                    $(this).parent().find('.ac_id').val('');

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.val('Укажите рег. номер техники!')
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
    }


    function load_places_rfr() {
        //console.log($("#suporgid").val());

        var selector = "#load_placeid";
        var save_ID = $(selector).val();
        //console.log('save_ID='+save_ID)

        $(selector + " > option").remove();

        if ($("#suporgid").val()) {

            $.get("/api/org_places/for_", {
                    s_orgid: $("#suporgid").val()
                },
                function (data) {
                    //console.log(data);

                    $(selector).append($("<option>"));
                    $.each(data.org_places, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });

                    $(selector).val(save_ID);
                    //console.log('restored save_ID=',$(selector).val())
                    //Если вариант всего один - попробуем сразу его выбрать. 2 - потому что есть еще placeholder
                    if ($(selector + ' option').length == 2) {
                        $(selector).prop('selectedIndex', 1);
                    }
                }
            );
        }
    }

    $("#suporgid").change(function () {
        load_places_rfr();
    });


    function unload_places_rfr() {
        //console.log($("#suporgid").val());

        var selector = "#unload_placeid";
        var save_ID = $(selector).val();
        //console.log('save_ID='+save_ID)

        $(selector + " > option").remove();

        if ($("#orgid").val()) {

            $.get("/api/org_places/for_", {
                    s_orgid: $("#orgid").val()
                },
                function (data) {
                    //console.log(data);

                    $(selector).append($("<option>"));
                    $.each(data.org_places, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });

                    $(selector).val(save_ID);
                    //console.log('restored save_ID=',$(selector).val())
                    //Если вариант всего один - попробуем сразу его выбрать. 2 - потому что есть еще placeholder
                    if ($(selector + ' option').length == 2) {
                        $(selector).prop('selectedIndex', 1);
                    }
                }
            );
        }
    }

    $("#orgid").change(function () {
        unload_places_rfr();
    });


    function rfr_iface() {
        //перерисовка интерфейса в зависимости от значений
        if ($("#in_gk").val() == 1) {
            if ($("#driverid").val() == 160)
                $("#driverid").val('');     //зачищаем 160
            $(".driver_info").show();
            $(".salary_info").show();

        } else {
            $(".driver_info").hide();
            $(".salary_info").hide();
            $("#driverid").val(160);    //160 - "-неизвестный--водитель-"
            $("#raid_salary").val(0);
        }

    }


    //при загрузке -------------------------------------------------

    rfr_iface();
    recalc_hrs();

    //покраска в зеленый всех автозаполняемых названий с установленными id в соответств. полях
    $.each($(".ac_name"), function (key, value) {
        //console.log( key + ": " + $(value).val() );
        if ($(this).parent().find('.ac_id').val()) {
            $(value).addClass("ac-act");

            //отобразить ссылку на карточку редактирования объекта справочника
            const id_lnk = $(this).parent().find('.id_lnk');
            id_lnk.hide()
            if (id_lnk && id_lnk.data('id') && id_lnk.data('obj'))
                id_lnk.show() //отобразить ссылку на карточку редактирования объекта справочника

        } else {
            $(this).parent().find('.id_lnk').hide()
        }
    });
});
