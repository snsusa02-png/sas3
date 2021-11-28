$(document).ready(function () {

    function check_lvltypeid() {

        var lvltypeid = $("#lvltypeid").val();
        //alert(lvltypeid);

        if (lvltypeid == '') { //уровень не выбран - скрываем все
            $(".l1h").hide();
            $(".l2h").hide();
            $(".l3h").hide();
        }
        if (lvltypeid == 1) { //группа - не редактируем кол-во и ЕИ
            $(".l1h").hide();
            $(".l1s").show();
        }
        if (lvltypeid == 2) {
            $(".l2h").hide();
            $(".l2s").show();
            $("#unit").attr('readonly', false);
        }
        if (lvltypeid == 3) {
            $(".l3h").hide();
            $(".l3s").show();
            $("#unit").attr('readonly', true);
        }
        // var refitmid = $("#refitmid").val();
        // var qty = $("#qty").val();
        // // console.log('qty=' + qty);
        // $.get("/refitems/getprice/", {refitmid: refitmid, orgid: orgid, qty: qty},
        //     function (data) {
        //         // console.log(data);
        //         // console.log(data.price);
        //         $("#price").val(data.price);
        //         $("#itmsum").val(data.price*qty);
        //
        //     }
        // )
    }

    $("#lvltypeid").change(function () {
        check_lvltypeid();
    });


    function check_startafter_planitmid() {
        var startafter_planitmid = $("#startafter_planitmid").val();
        if (startafter_planitmid != '') {
            $(".h_aft").hide();
            $(".s_aft").show();
        } else {
            $(".h_aft").show();
            $(".s_aft").hide();
        }
    }


    $("#begtypeid").change(function () {
        check_begtypeid();
    });

    function check_begtypeid() {
        var begtypeid = $("#begtypeid").val();
        if (begtypeid == 1) // ручное указание
        {
            $(".h_aft").show();
            $(".s_aft").hide();
        } else if (begtypeid == 2) // от предыдущей работы
        {
            $(".h_aft").hide();
            $(".s_aft").show();
        } else {
            $(".h_aft").hide();
            $(".s_aft").hide();
        }
    }

    $("#startafter_planitmid").change(function () {
        check_startafter_planitmid()
    });

    function calc_esttotwrkhrs() {
        var plnqty = parseFloat($("#plnqty").val());    //Объем работы
        var normpersonunithours = parseFloat($("#normpersonunithours").val());    //норма: количество часов 1 рабочего на выполнение 1ЕИ
        var rslt = Math.ceil(plnqty * normpersonunithours);
        $("#esttotwrkhrs").val(rslt);
    }

    $("#plnqty,#normpersonunithours").change(function () {
        var normpersonunithours = parseFloat($("#normpersonunithours").val());    //
        if (normpersonunithours > 0) {
            $("#estbynorm").show();
            $("#normspeed").val(1 / normpersonunithours);
            var plnqty = parseFloat($("#plnqty").val());    //Объем работы
            $("#esttotwrkhrs,#wrkhrs_by_norm").val(Math.ceil(plnqty * normpersonunithours));
            calc_plnenddt();

        } else {
            $("#estbynorm").hide();
            $("#normspeed").val('');
            $("#esttotwrkhrs").val('');
        }

        calc_esttotwrkhrs();
    });

    $("#set_plnworkhrs_by_esttotwrkhrs").click(function () {
        var plnworkhrs = $("#plnworkhrs").val();
        //if (!plnworkhrs || 0 === plnworkhrs.length) {
        $("#plnworkhrs").val($("#esttotwrkhrs").val());
        calc_estenddt();
        //}
    });


    function calc_plnenddt() {
        var schdldayhrs = parseInt($("#schdldayhrs").val());  //продолжительность рабочего дня, часов
        if (schdldayhrs > 0) {

            //$("#plnworkdays").change();

            var plnworkhrs = $("#plnworkhrs").val();    //всего нужно часов на работу
            var plnbegdt = $("#plnbegdt").val();        //планируемый момент начала работ
            var workbegtime = $("#workbegtime").val();
            var workendtime = $("#workendtime").val();
            var breakbegtime = $("#breakbegtime").val();
            var breakendtime = $("#breakendtime").val();

            if (plnbegdt && plnworkhrs && workbegtime && workendtime && breakbegtime && breakendtime) {
                var plnbegdt = moment(plnbegdt);
                var plnbeg_hr = plnbegdt.hour();    //час начала работы
                //console.log('plnbeg_hr=' + plnbeg_hr);

                var workbeg_hr = parseInt(workbegtime.substr(0, 2)); //час начала рабочей смены
                var workend_hr = parseInt(workendtime.substr(0, 2));

                var breakbeg_hr = parseInt(breakbegtime.substr(0, 2)); //час начала перерыва
                var breakend_hr = parseInt(breakendtime.substr(0, 2));
                var break_hrs = breakend_hr - breakbeg_hr;
                // console.log('break duration= ' + break_hrs);

                workend_hr = workbeg_hr + schdldayhrs;
                //если рабояий день начинается ДО перерыва, и заканчивается ПОСЛЕ, то добавим еще продолжительность перерыва
                if (workbeg_hr < breakbeg_hr && workend_hr > breakbeg_hr) {
                    workend_hr += break_hrs;
                }

                //проверим, чтобы рабочий день заканчивался до окончания суток (упрощаем - только до 2300)
                if (workend_hr > 23) {
                    workend_hr = 23;
                    schdldayhrs = workend_hr - workbeg_hr - 1;
                    $("#schdldayhrs").val(schdldayhrs);
                }
                $("#workendtime").val(workend_hr + ":00");

                var cycle_lim = 1000;   //защита от бесконечности :)
                while (plnworkhrs > 0 && cycle_lim > 0) {
                    var hrs2dayend = workend_hr - plnbegdt.hour();
                    //console.log('hrs2dayend=' + hrs2dayend);
                    //если работа началась до обеденного перерыва, то остаток нужно уменьшить на длину перерыва
                    if (plnbeg_hr < breakbeg_hr) {
                        hrs2dayend -= break_hrs;
                    }
                    //если часов на работу нужно меньше, чем осталось до окончания рабочего дня, то
                    // просто добавим часы к началу работы
                    var plnenddt = moment(plnbegdt);
                    if (plnworkhrs <= hrs2dayend) {
                        plnenddt = plnenddt.add(plnworkhrs, 'hours');
                        //если работа началась ДО перерыва, а закончилась ПОСЛЕ, то добавим еще продолжительность перерыва
                        if (plnbeg_hr < breakbeg_hr && plnenddt.hour() > breakbeg_hr) {
                            plnenddt = plnenddt.add(break_hrs, 'hours');
                        }
                        plnworkhrs = 0;
                    } else {
                        //сдвинемся на начало след. дня
                        plnbegdt = plnbegdt.hour(workbeg_hr).add(1, 'days');
                        plnworkhrs -= hrs2dayend;
                        //console.log('plnworkhrs=' + plnworkhrs);
                    }
                    cycle_lim -= 1;
                }

                // console.log('plnenddt=' + plnenddt.format("YYYY-MM-DD[T]HH:mm"));
                plnenddt = plnenddt.format("YYYY-MM-DD[T]HH:mm");
                $("#plnenddt").val(plnenddt); //планируемый момент окончания работы


                var rest_workhrs = plnworkhrs - hrs2dayend;
                // console.log('rest_workhrs=' + rest_workhrs);
                //console.log('hrs2dayend=' + hrs2dayend);
            }
        }
    }

    function calc_estenddt() {
        var schdldayhrs = parseInt($("#schdldayhrs").val());  //продолжительность рабочего дня, часов
        if (schdldayhrs > 0) {

            //$("#plnworkdays").change();

            var estbegdt = $("#estbegdt").val();            //планируемый момент начала работ
            var drctenddt = moment($("#drctenddt").val()+" 17:00");  //директивное окончание работ

            var workbegtime = $("#workbegtime").val();
            var workendtime = $("#workendtime").val();
            var breakbegtime = $("#breakbegtime").val();
            var breakendtime = $("#breakendtime").val();

            var restqty = $("#restqty").val();
            var avg_per_hour = $("#avg_per_hour").val();

            //всего нужно часов на работу ---------------------------------------------------------------------

            //если есть норма, подсчитаем для нее
            var plnworkhrs = Math.ceil($("#normpersonunithours").val() * restqty / $("#plnworkercnt").val());
            $("#wrkhrs_by_norm").val(plnworkhrs);
            var dt = calcEstEndDT(estbegdt, plnworkhrs, schdldayhrs, workbegtime, workendtime, breakbegtime, breakendtime);
            //alert(dt);
            $("#norm_estenddt").val(dt);
            dt = moment(dt);
            if (drctenddt.isBefore(dt)) {
                $("#norm_estenddt").css('color','red');
            }else
                $("#norm_estenddt").css('color','green');


            if (avg_per_hour > 0) {
                //если есть факт, то будем использовать его для оценки
                // plnworkhrs = Math.ceil(restqty / avg_per_hour);
                // $("#wrkhrs_by_avg").val(plnworkhrs);
                plnworkhrs = $("#wrkhrs_by_avg").val();
                var dt = calcEstEndDT(estbegdt, plnworkhrs, schdldayhrs, workbegtime, workendtime, breakbegtime, breakendtime);
                $("#avg_estenddt").val(dt);
                dt = moment(dt);
                if (drctenddt.isBefore(dt)) {
                    $("#avg_estenddt").css('color','red');
                }else
                    $("#avg_estenddt").css('color','green');
            }
        }
    }

    function calcEstEndDT(fctbegdt, plnworkhrs, schdldayhrs, workbegtime, workendtime, breakbegtime, breakendtime) {
        var plnenddt = null;

        if (fctbegdt && plnworkhrs && workbegtime && workendtime && breakbegtime && breakendtime) {
            // console.log('fctbegdt=' + fctbegdt);
            // console.log('plnworkhrs=' + plnworkhrs);

            var plnbegdt = moment(fctbegdt);
            var plnbeg_hr = plnbegdt.hour();    //час начала работы
            // console.log('plnbeg_hr=' + plnbeg_hr);

            var workbeg_hr = parseInt(workbegtime.substr(0, 2)); //час начала рабочей смены
            var workend_hr = parseInt(workendtime.substr(0, 2));

            var breakbeg_hr = parseInt(breakbegtime.substr(0, 2)); //час начала перерыва
            var breakend_hr = parseInt(breakendtime.substr(0, 2));
            var break_hrs = breakend_hr - breakbeg_hr;
            // console.log('break duration= ' + break_hrs);

            workend_hr = workbeg_hr + schdldayhrs;
            //если рабочий день начинается ДО перерыва, и заканчивается ПОСЛЕ, то добавим еще продолжительность перерыва
            if (workbeg_hr < breakbeg_hr && workend_hr > breakbeg_hr) {
                workend_hr += break_hrs;
            }

            //проверим, чтобы рабочий день заканчивался до окончания суток (упрощаем - только до 2300)
            if (workend_hr > 23) {
                workend_hr = 23;
                schdldayhrs = workend_hr - workbeg_hr - 1;
                $("#schdldayhrs").val(schdldayhrs);
            }
            $("#workendtime").val(workend_hr + ":00");

            var cycle_lim = 1000;   //защита от бесконечности :)
            while (plnworkhrs > 0 && cycle_lim > 0) {
                var hrs2dayend = workend_hr - plnbegdt.hour();
                //console.log('hrs2dayend=' + hrs2dayend);
                //если работа началась до обеденного перерыва, то остаток нужно уменьшить на длину перерыва
                if (plnbeg_hr < breakbeg_hr) {
                    hrs2dayend -= break_hrs;
                }
                //если часов на работу нужно меньше, чем осталось до окончания рабочего дня, то
                // просто добавим часы к началу работы
                var plnenddt = moment(plnbegdt);
                if (plnworkhrs <= hrs2dayend) {
                    plnenddt = plnenddt.add(plnworkhrs, 'hours');
                    //если работа началась ДО перерыва, а закончилась ПОСЛЕ, то добавим еще продолжительность перерыва
                    if (plnbeg_hr < breakbeg_hr && plnenddt.hour() > breakbeg_hr) {
                        plnenddt = plnenddt.add(break_hrs, 'hours');
                    }
                    plnworkhrs = 0;
                } else {
                    //сдвинемся на начало след. дня
                    fctbegdt = plnbegdt.hour(workbeg_hr).add(1, 'days');
                    plnworkhrs -= hrs2dayend;
                    //console.log('plnworkhrs=' + plnworkhrs);
                }
                cycle_lim -= 1;
            }

            // console.log('plnenddt=' + plnenddt.format("YYYY-MM-DD[T]HH:mm"));
            plnenddt = plnenddt.format("YYYY-MM-DD[T]HH:mm");
            //$("#estenddt").val(plnenddt); //планируемый момент окончания работы


            var rest_workhrs = plnworkhrs - hrs2dayend;
            // console.log('rest_workhrs=' + rest_workhrs);
            //console.log('hrs2dayend=' + hrs2dayend);
        }
        return (plnenddt); //планируемый момент окончания работы
    }


    $("#estbegdt,#plnworkhrs,#plnbegdt,#schdldayhrs,#workbegtime,#workendtime,#plnworkercnt,#wrkhrs_by_avg,#wrkhrs_by_norm").change(function () {
        //calc_plnenddt();
        calc_estenddt();
    });


    $("#plnworkdays").change(function () {

        var plnworkdays = parseFloat($("#plnworkdays").val());    //Продолжительность работ в днях
        //var plnworkhrs = parseInt($("#plnworkhrs").val());
        var schdldayhrs = parseFloat($("#schdldayhrs").val());    //Продолжительность рабочей смены
        if (schdldayhrs > 0)
            var rslt = Math.ceil(plnworkdays * schdldayhrs);
        else
            var rslt = '';
        $("#plnworkhrs").val(rslt);
    });
    $("#plnworkhrs").change(function () {
        var plnworkhrs = parseInt($("#plnworkhrs").val());      //Продолжительность работ в часах
        var schdldayhrs = parseFloat($("#schdldayhrs").val());    //Продолжительность рабочей смены
        if (schdldayhrs > 0)
            var rslt = Math.ceil(plnworkhrs / schdldayhrs);
        else
            var rslt = '';
        $("#plnworkdays").val(rslt);

    });


    //$("#lvltypeid").change();
    calc_esttotwrkhrs();
    check_lvltypeid();
    check_startafter_planitmid();
    check_begtypeid();

    calc_estenddt();
});
