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
        const begtime = $("#begtime").val();
        const endtime = $("#endtime").val();
        const break_hrs = 0; //parseFloat($("#break_hrs").val());

        var stfwrkhrs = '-';
        if (begtime && endtime && !isNaN(break_hrs)) {
            var t1 = begtime.split(':'), t2 = endtime.split(':');
            var d1 = new Date(0, 0, 0, t1[0], t1[1]),
                d2 = new Date(0, 0, 0, t2[0], t2[1]);
            //var stfwrkhrs = Math.round(10*((d2 - d1)/3600000 - break_hrs))/10;
            var stfwrkhrs = Math.round(10 * ((d2 - d1) / 3600000 - break_hrs)) / 10;
        }
        $("#mchnwrkhrs").val(stfwrkhrs);
        //console.log(stfwrkhrs)
    }

    $("#begtime, #endtime, #break_hrs").change(function () {
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


    if ($(".staff_name").length > 0) {
        $(".staff_name").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/orgstaff/autocomplete/search",
                    dataType: "json",
                    data: {
                        q: request.term,
                       //flagid: 187,    //признак водителя
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

    if ($(".orgcharge_name").length > 0) {

        $(".orgcharge_name").autocomplete({
            source: function (request, response) {
                //console.log(request.term)

                $.ajax({
                    url: "/api/org_charges/for_ac/",
                    dataType: "json",
                    data: {
                        name: request.term,
                        orgid: $('#orgid').val(),    //организация сотрудника
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

                            var charge_sum = item.dir * item.charge_sum;
                            var lbl = item.name + " (" + charge_sum + ")";
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                                //orgid: item.orgid,
                                charge_sum: item.charge_sum
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

                    // Дополнительные поля -------------------------------------------
                    //var charge_sum = $(this).parent().parent().find('.charge_sum');
                    var charge_sum = $('.charge_sum').first();
                    charge_sum.val(ui.item.charge_sum);
                    //----------------------------------------------------------------

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

    //rfr_iface();

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
