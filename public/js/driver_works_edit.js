$(document).ready(function () {


    function checkForInput(element) {
        // element is passed to the function ^

        // const $label = $(element).siblings('label');
        var n = parseFloat($(element).val());

        //if ($(element).val().length > 0) {
        if (isNaN(n) || n == 0) {
            $(element).removeClass('has-value');
        } else {
            $(element).addClass('has-value');
        }
    }

    // The lines below are executed on page load
    //     $('input.textdemo').each(function() {
    $('input[type="number"]').each(function () {
        checkForInput(this);
    });

    // The lines below (inside) are executed on change & keyup
    $('input[type="number"]').on('change keyup', function () {
        checkForInput(this);
    });

    function recalc_hrs() {
        //console.log('recalc_HRS')
        //console.log(moment('2020-01-01').set('year', moment().get('year')).format('yyyy-MM-DD'));
        // if ($("#wrkdate").val() != '')
        //     $("#wrkenddate").attr('min', $("#wrkdate").val());

        // if ($("#wrkenddate").val() == '' && $("#wrkdate").val() != '')
        //     $("#wrkenddate").val($("#wrkdate").val());


        // if (1 == 0 && enddt < begdt) {
        //     $("#wrkenddate").val($("#wrkdate").val());
        //     $("#endtime").val($("#begtime").val());
        //     var enddt = moment($("#wrkenddate").val() + ' ' + $("#endtime").val());
        // }

        var day_brkhrs = parseFloat($("#day_brkhrs").val());
        //day_brkhrs = (isNaN(day_brkhrs) || day_brkhrs < 0) ? 0 : day_brkhrs;
        if (isNaN(day_brkhrs) || day_brkhrs < 0) {
            day_brkhrs = 0;
            $("#day_brkhrs").val(day_brkhrs);
        }

        var day_hr_rate = parseFloat($("#day_hr_rate").val());
        day_hr_rate = isNaN(day_hr_rate) ? 0 : day_hr_rate;
        // console.log('day_hr_rate = ' + day_hr_rate)

        var night_brkhrs = parseFloat($("#night_brkhrs").val());
        //night_brkhrs = (isNaN(night_brkhrs) || night_brkhrs < 0) ? 0 : night_brkhrs;
        if (isNaN(night_brkhrs) || night_brkhrs < 0) {
            night_brkhrs = 0.0;
            $("#night_brkhrs").val(night_brkhrs);
        }

        var night_hr_rate = parseFloat($("#night_hr_rate").val());
        night_hr_rate = isNaN(night_hr_rate) ? 0 : night_hr_rate;

        var begdt = moment($("#wrkdate").val() + ' ' + $("#begtime").val());
        var enddt = moment($("#wrkenddate").val() + ' ' + $("#endtime").val());
        //console.log(begdt, enddt, (enddt<begdt), (enddt>=begdt));
        //console.log(begdt.format('DD.MM.yyyy HH:mm'), enddt.format('DD.MM.yyyy HH:mm'));
        //console.log(begdt, enddt);

        // var fctenddt = begdt.add(3, 'hours');
        // console.log(fctenddt);
        //console.log(enddt.diff(begdt, 'hours', true));
        //var stfwrkhrs = Math.round((enddt.diff(begdt, 'hours', true) - day_brkhrs - night_brkhrs) * 10) / 10;
        var stfwrkhrs = Math.round((enddt.diff(begdt, 'hours', true)) * 100) / 100;
        if (isNaN(stfwrkhrs))
            $("#stfwrkhrs").val('-');
        else
            $("#stfwrkhrs").val(stfwrkhrs);
        //console.log(stfwrkhrs)

        var day_wrkhrs = 0;
        var night_wrkhrs = 0;
        var hrs_salary = 0;

        //var lim_dt = moment().subtract(2, 'month'); // два месяца назад от текущего дня
        //console.log(moment.duration(enddt.diff(begdt)).asDays());
        if (moment.duration(enddt.diff(begdt)).asDays() < 10) {
            // не запускаем расчет часов, если указана (скорее всего ошибочно) слишком ранняя дата
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

            day_wrkhrs = Math.round((day_hrs - Math.min(day_brkhrs, day_hrs)) * 100) / 100;
            night_wrkhrs = Math.round((night_hrs - Math.min(night_brkhrs, night_hrs)) * 100) / 100;
            console.log(day_wrkhrs, night_wrkhrs)
            hrs_salary = Math.round((
                day_wrkhrs * day_hr_rate
                + night_wrkhrs * night_hr_rate
            ) * 100) / 100;
            // console.log('hrs_salary = ' + hrs_salary)
        }

        $("#day_hrs").val(Math.round(day_hrs * 100) / 100);
        $("#night_hrs").val(Math.round(night_hrs * 100) / 100);
        $("#day_wrkhrs").val(day_wrkhrs);
        $("#night_wrkhrs").val(night_wrkhrs);
        $("#hrs_salary").val(hrs_salary);

        recalc_salary();
    }

    $("#wrkdate, #begtime, #wrkenddate, #endtime, #day_brkhrs, #night_brkhrs, #aux_equipment").change(function () {
        recalc_hrs();
    });

    function recalc_breaks() {

        // Расчет полной суммы ЗП по простоям ----------------------------
        var breaks_sum = 0;

        $('.break_item').each(function () {
            hr_sum = 0;
            h = parseFloat($(this).find('.aux_day_hrs').val());
            r = parseFloat($(this).find('.aux_hr_day_rate').val());
            h = (isNaN(h)) ? 0 : h;
            r = (isNaN(r)) ? 0 : r;
            hr_sum += h * r;

            h = parseFloat($(this).find('.aux_night_hrs').val());
            r = parseFloat($(this).find('.aux_hr_night_rate').val());
            h = (isNaN(h)) ? 0 : h;
            r = (isNaN(r)) ? 0 : r;
            hr_sum += h * r;

            $(this).find('.aux_hr_sum').val(hr_sum);
            breaks_sum += hr_sum;
        });

        // Проход по явно-указанной ЗП по строке --------------------------
        $('.aux_aux_sum').each(function () {
            s = parseFloat($(this).val());
            s = (isNaN(s)) ? 0 : s;
            //console.log(s)
            breaks_sum += s;
        });

        //Установим рассчитанную сумму ЗП по простоям
        $("#breaks_sum").val(breaks_sum);
        //----------------------------------------------------------------

        recalc_hrs();
    }


    $(".aux_aux_sum").change(function () {
        recalc_breaks();
    });


    // --- При изменении какого-то из часов простоя днем ---
    $(".aux_day_hrs").change(function () {
        //console.log($(this).val());
        var hr = parseFloat($(this).val());
        if (isNaN(hr) || hr < 0) {
            hr = 0;
            $(this).val(hr);
        }

        //Подсчитаем общее кол-во дневных счетов
        var tot_hrs = 0;
        $('.aux_day_hrs').each(function () {
            h = parseFloat($(this).val());
            tot_hrs += (isNaN(h)) ? 0 : h;
        });
        //console.log(tot_hrs);

        //Кол-во часов простоя днем не может превыщать общее кол-во рабочих часов днем
        var lim_hrs = parseFloat($("#day_hrs").val());
        if (tot_hrs > lim_hrs) {
            hr -= tot_hrs - lim_hrs;
            $(this).val(hr);
            tot_hrs = lim_hrs;
        }

        //вычислим почасовую ЗП по строке (day+night) --------------------------
        var h;
        var r;
        var hr_sum = 0;

        var line = $(this).parent().parent();
        // console.log(line)
        h = parseFloat(line.find('.aux_day_hrs').val());
        h = (isNaN(h)) ? 0 : h;
        r = parseFloat(line.find('.aux_hr_day_rate').val());
        r = (isNaN(r)) ? 0 : r;
        hr_sum += h * r;

        h = parseFloat(line.find('.aux_night_hrs').val());
        h = (isNaN(h)) ? 0 : h;
        r = parseFloat(line.find('.aux_hr_night_rate').val());
        r = (isNaN(r)) ? 0 : r;
        hr_sum += h * r;

        line.find('.aux_hr_sum').val(hr_sum);
        //----------------------------------------------------------------------

        $("#day_brkhrs").val(tot_hrs);  // Для "верхнего", итогового значения по дневным часам

        recalc_breaks();
    });

    // --- При изменении какого-то из часов простоя ночью ---
    $(".aux_night_hrs").change(function () {
        //console.log('aux_night_hrs = ' + $(this).val());
        var hr = parseFloat($(this).val());
        hr = (isNaN(hr) || hr < 0) ? 0 : hr;
        $(this).val(hr);

        // Проход по ночным часам ----------------
        var tot_hrs = 0;
        $('.aux_night_hrs').each(function () {
            h = parseFloat($(this).val());
            tot_hrs += (isNaN(h) || h < 0) ? 0 : h;
        });
        //console.log(tot_hrs);
        // Проверка на превышение общего кол-ва ночных часов
        var lim_hrs = parseFloat($("#night_hrs").val());
        //console.log('night_hrs = ' + $("#night_hrs").val());
        if (tot_hrs > lim_hrs) {
            hr -= tot_hrs - lim_hrs;
            $(this).val(hr);
            tot_hrs = lim_hrs;
        }
        // Установим общее кол-во ночных часов
        $("#night_brkhrs").val(tot_hrs);

        //вычислим почасовую ЗП по строке (day+night) --------------------------
        var h;
        var r;
        var hr_sum = 0;

        var line = $(this).parent().parent();
        // console.log(line)
        h = parseFloat(line.find('.aux_day_hrs').val());
        h = (isNaN(h)) ? 0 : h;
        r = parseFloat(line.find('.aux_hr_day_rate').val());
        r = (isNaN(r)) ? 0 : r;
        hr_sum += h * r;

        h = parseFloat(line.find('.aux_night_hrs').val());
        h = (isNaN(h)) ? 0 : h;
        r = parseFloat(line.find('.aux_hr_night_rate').val());
        r = (isNaN(r)) ? 0 : r;
        hr_sum += h * r;

        line.find('.aux_hr_sum').val(hr_sum);
        //----------------------------------------------------------------------

        recalc_breaks();
    });


    $("#wrkdate, #staffid, #machineid, #wrktypeid").change(function () {
        //получить данные по рейсам выбранного авто за указанный день и ставки почасовой оплаты -----
        raid_info_rfr();
    });


    function recalc_salary() {
        var hrs_salary = parseFloat($("#hrs_salary").val());
        var breaks_sum = parseFloat($("#breaks_sum").val());
        var raid_sum = parseFloat($("#raid_sum").val());
        // var pdt_cost = parseFloat($("#pdt_cost").val());
        // var pdt_hrs = parseFloat($("#pdt_hrs").val());
        // var repair_cost = parseFloat($("#repair_cost").val());
        // var repair_hrs = parseFloat($("#repair_hrs").val());


        hrs_salary = (isNaN(hrs_salary)) ? 0 : hrs_salary
        breaks_sum = (isNaN(breaks_sum)) ? 0 : breaks_sum
        raid_sum = (isNaN(raid_sum)) ? 0 : raid_sum
        // pdt_cost = (isNaN(pdt_cost)) ? 0 : pdt_cost
        // pdt_hrs = (isNaN(pdt_hrs)) ? 0 : pdt_hrs
        // repair_cost = (isNaN(repair_cost)) ? 0 : repair_cost
        // repair_hrs = (isNaN(repair_hrs)) ? 0 : repair_hrs

        // pdt_sum = Math.round(100 * pdt_cost * pdt_hrs) / 100;
        // $("#pdt_sum").val(pdt_sum);

        // repair_sum = Math.round(100 * repair_cost * repair_hrs) / 100;
        // $("#repair_sum").val(repair_sum);

        var salary_sum = hrs_salary + breaks_sum + raid_sum;
        $("#salary_sum").val(salary_sum);
        //console.log(salary_sum)
    }

    $("#hrs_salary, #breaks_sum, #raid_sum").change(function () {
        recalc_salary();
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


    function raid_info_rfr() {
        //получить данные по рейсам выбранного авто за указанный день -----
        //console.log('before raid_info_rfr--------------------------------')
        $.get("/api/mchn_raids/data_for_driver_works",
            {
                machineid: $("#machineid").val()
                , driverid: $("#staffid").val()
                , wrkdate: $("#wrkdate").val()
                , wrktypeid: $("#wrktypeid").val()
            },
            function (data) {
                //console.log('get data raid_info_rfr--------------------------------')
                //console.log(data);
                //console.log(data.data.raid_salary_sum);
                $("#raid_qty").val(data.data.raid_qty);
                $("#raid_sum").val(data.data.raid_salary_sum);
                $("#day_hr_rate").val(data.data.hr_day_rate);
                $("#night_hr_rate").val(data.data.hr_night_rate);

                //console.log(data.data.break_rates);
                var break_rates = data.data.break_rates;
                break_rates.forEach(function (item, i, break_rates) {
                    /*console.log( i + ": " + item.wrktypeid
                            + ", day_rate = " + item.hr_day_rate
                            + ", night_rate = " + item.hr_night_rate);*/
                    $("#hr_day_rate_wt" + item.wrktypeid).val(item.hr_day_rate);
                    $("#hr_night_rate_wt" + item.wrktypeid).val(item.hr_night_rate);
                });

                // пересчитать ЗП от часов, начиная с расчета простоев
                recalc_breaks();
            }
        )
        //-----------------------------------------------------------------
    }

    function buildopertypeid_rfr() {
        var selector = "#orgcontractid";
        var save_ID = $(selector).val();
        //console.log(save_ID);
        $(selector + " > option").remove()
        $.get("/api/contracts/buildopertypeid", {buildopertypeid: $("#buildopertypeid").val()},
            function (data) {
                //console.log(data);

                $(selector + " > option").remove()
                $(selector).append($("<option>"))
                $.each(data.orgcontracts, function (index, value) {
                    $(selector).append($("<option>").attr("value", index).append(value))
                });
            }
        )
        $(selector).val(save_ID);
    }


    $("#buildopertypeid").change(function () {
        buildopertypeid_rfr();
    });

    // $("#machine_name").change(function (e) {
    //     console.log($("#machine_name").val());
    // });
    $(".stfname").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "/orgstaff/autocomplete/search",
                dataType: "json",
                data: {
                    q: request.term,
                    aux:"hrs_salary"
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
                            orgname: item.orgname,
                            opertypeid: item.opertypeid
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
                //var staffid = $(this).parent().find('.staffid');
                var staffid = $(this).parent().find('.ac_id');
                staffid.val(ui.item.id);
                var orgid = $(this).parent().find('.orgid');
                orgid.val(ui.item.orgid);
                // var opertypeid = $(this).parent().parent().find('.opertypeid');
                // переделать на относительный поиск по классу?
                var opertypeid = $('#opertypeid');
                opertypeid.val(ui.item.opertypeid);
                $(this).val(ui.item.label);

                // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                var ac_status = $(this).parent().find('.ac_status');
                ac_status.hide().removeClass("ac-fail");
                $(this).addClass("ac-act");


                //получить данные по рейсам выбранного авто за указанный день -----
                raid_info_rfr()
                //-----------------------------------------------------------------

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
    };


    $(".machine_name").autocomplete({
        source: function (request, response) {
            //$.get("/api/budgets/for_", {buildobjid: s_buildobjid, in_equiprsts: 1},

            $.ajax({
                url: "/api/machines/for_ac",
                dataType: "json",
                data: {
                    //q: request.term,
                    s_name: request.term,
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
                // console.log($(this).parent().find('.machineid').val());
                var machineid = $(this).parent().find('.machineid');
                machineid.val(ui.item.id);
                var orgid = $(this).parent().find('.orgid');
                orgid.val(ui.item.orgid);
                $(this).val(ui.item.label);

                // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                var ac_status = $(this).parent().find('.ac_status');
                ac_status.hide().removeClass("ac-fail");
                $(this).addClass("ac-act");

                //получить режимы эксплуатации -----
                if (1 == 0) {
                    var selector = "#mot_id";
                    var save_ID = $(selector).val();
                    //console.log(save_ID);
                    $(selector + " > option").remove()
                    $.get("/api/mchn_opertypes/for_", {machineid: $("#machineid").val()},
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

                        }
                    )
                    $(selector).val(save_ID);
                }

                //получить данные по рейсам выбранного авто за указанный день -----
                raid_info_rfr()
                //-----------------------------------------------------------------

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
            if ($(this).val().length == 0) {
                $(this).parent().find('.machineid').val('');

                $(this).parent().find('.machine_name')
                    .removeClass("ac-act")
                    .addClass("ac-fail");

                var ac_status = $(this).parent().find('.ac_status');
                ac_status.val('Укажите рег. номер техники!')
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
    };

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


    //при загрузке -------------------------------------------------

    //rfr_iface();
    raid_info_rfr();
    recalc_breaks();
    //recalc_hrs();

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
