$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('.grid-sessions').click(function (e) {

        let calc = $(this).val();
        //console.log(e.pageX + ' , ' + e.pageY)

        let obj_num = Math.trunc((e.pageX - 60) / 120);
        //console.log('obj_num=' + obj_num);
        let resobjid = $("#obj-" + obj_num).attr('data-obj-id');

        //console.log('resobjid=' + resobjid);

        //let time_slot = Math.trunc((e.pageY - 24) / 50);
        let time_slot = Math.trunc((e.pageY - 64) / 50);
        console.log('time_slot=' + time_slot);
        let workbegdt = moment('2021-09-02 07:00');
        let begdt = moment(workbegdt).add(time_slot * 30, 'minutes');
        let enddt = moment(begdt).add(30, 'minutes');
        //console.log(workbegdt)
        console.log(moment(begdt))
        console.log(moment(enddt))
        //особый формат для поля типа datetime-local
        $("#start").val(moment(begdt).format("YYYY-MM-DDTHH:mm:ss"));
        $("#end").val(moment(enddt).format("YYYY-MM-DDTHH:mm:ss"));

        $("#eventid").val('')
        $("#orgid").val(curUserOrgID)
        $("#resobjid").val(resobjid)
        $(".reguser").hide();
        $("#btnDelete").hide();

        //Получим данные об объекте и времени
        $.ajax({
            url: "/api/booking/resobj_ts",
            dataType: "json",
            data: {
                resobjid: resobjid,
                ts: time_slot,
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (data) {
                console.log(data);

                //var ord_name = data.data.order.name;
                // $(".modal-title").html('Заявка #' + event_id + ' - ' + data.data.resobj.name)
                // $("#regusername").html(data.data.creator.name)
                // $("#eventid").val('')
                // $("#txtTitle").val(ord_name)
                //
                // //особый формат для поля типа datetime-local
                // $("#start").val(moment(data.data.begdt).format("YYYY-MM-DDTHH:mm:ss"));
                // $("#end").val(moment(data.data.enddt).format("YYYY-MM-DDTHH:mm:ss"));
                //
                var start = moment($("#start").val());
                var end = moment($("#end").val());
                var duration = moment.duration(end.diff(start));
                $("#duration").val(duration.asHours());

                // if (rest_sum < $("#docsum").val())
                //     $("#budget_rest").addClass('text-danger')
                // else
                //     $("#budget_rest").removeClass('text-danger')
            }
        });


        //$(".modal-title").html('Новая заявка ' + ' - ' + data.data.resobj.name)
        $(".modal-title").html('Новая заявка ' + ' - ' + obj_num)
        //$("#regusername").html(data.data.creator.name)
        //$("#txtTitle").val(ord_name)
        //$("#orgid").val(data.data.order.orgid)

        //особый формат для поля типа datetime-local
        //$("#start").val(moment(data.data.begdt).format("YYYY-MM-DDTHH:mm:ss"));
        //$("#end").val(moment(data.data.enddt).format("YYYY-MM-DDTHH:mm:ss"));

        $("#btnBook").html('Создать').removeClass('btn-success').addClass('btn-primary');

        //откроем форму
        $("#myModal").modal('show')

    });


    $('.grid-session-cell').click(function (event) {

        event.stopPropagation();
        let event_id = $(this).attr("data-event-id");

        //console.log(event_id)
        //console.log(SITEURL)

        //Получим данные о заявке
        $.ajax({
            url: "/api/booking/by_id",
            dataType: "json",
            data: {
                id: event_id,
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (data) {
                //console.log(data);

                var ord_name = data.data.order.name;
                $(".modal-title").html('Заявка #' + event_id + ' - ' + data.data.resobj.name)
                $("#regusername").html(data.data.creator.name)
                $("#eventid").val(event_id)
                $("#resobjid").val(data.data.resobjid)
                $("#txtTitle").val(ord_name)
                $("#orgid").val(data.data.order.orgid)

                //особый формат для поля типа datetime-local
                $("#start").val(moment(data.data.begdt).format("YYYY-MM-DDTHH:mm:ss"));
                $("#end").val(moment(data.data.enddt).format("YYYY-MM-DDTHH:mm:ss"));

                var start = moment($("#start").val());
                var end = moment($("#end").val());
                var duration = moment.duration(end.diff(start));
                $("#duration").val(duration.asHours());

                // if (rest_sum < $("#docsum").val())
                //     $("#budget_rest").addClass('text-danger')
                // else
                //     $("#budget_rest").removeClass('text-danger')

                $("#btnBook").html('Сохранить').removeClass('btn-success').addClass('btn-primary');

            }
        });

        //откроем форму
        $("#myModal").modal('show')
    });


    $("#btnBook").click(function () {

        var txtTitle = $("#txtTitle").val();

        if (txtTitle) {
            txtTitle = encodeURIComponent(txtTitle);
            var eventid = $("#eventid").val();
            //console.log(eventid)
            //start = sessionStorage.getItem('start_date');
            //end = sessionStorage.getItem('end_date');
            allDay = false;
            var userid = $("#userid").val();
            var orgid = $("#orgid").val();
            var resobjid = $("#resobjid").val();
            var start = $("#start").val();
            var end = $("#end").val();
            var color = $("#color").val();
            //var event_place = $("#event_place").val();
            //var public = ($("#public").prop('checked')) ? 1 : 0;
            //var public_lvl = $("#public_lvl").val();
            var descript = encodeURIComponent($("#descript").val());
            //var tags = $("#tags").val();
            //var buildobjid = $("#buildobjid").val();

            // var notify_before = '';
            // if ($("#setNotify").prop('checked')) {
            //     notify_before = pad($("#notifyDays").val(), 1) + ' '
            //         + pad($("#notifyHours").val(), 2) + ':'
            //         + pad($("#notifyMinutes").val(), 2) + ':00';
            // }
            //alert(notify_before);


            var url = (eventid) ? "/booking/update" : "/booking/create";
            //console.log(url);

            $.ajax({
                url: url,
                data: 'id=' + eventid + '&title=' + txtTitle + '&start=' + start
                    + '&end=' + ((end) ? end : start)
                    //+ '&event_place=' + event_place
                    + '&resobjid=' + resobjid
                    + '&orgid=' + orgid
                    + '&userid=' + userid
                    //+ '&color=' + color
                    //+ '&public_lvl=' + public_lvl
                    + '&descript=' + descript
                //+ '&notify_before=' + notify_before
                //+ '&tags=' + tags
                ,
                type: "POST",
                success: function (data) {
                    //console.log(data);
                    displayMessage("Успешно сохранено");
                }
            });

            window.location.reload();

        } else
            alert('Укажите описание заявки!');
        //$("#myModal").modal('hide');
        //clearForm();
    });

    $(".modal-close").click(function () {
        $("#myModal").modal('hide');
        clearForm();

    });

    $("#btnDelete").click(function () {
        var eventid = $("#eventid").val();
        if (eventid && confirm("Удалить запись?")) {

            $.ajax({
                url: "/booking/delete",
                data: 'id=' + eventid,
                type: "POST",
                success: function (data) {
                    //console.log(data);
                    displayMessage('Успешно сохранено');
                }
            });

            window.location.reload();
        }
        $("#myModal").modal('hide');
        clearForm();
    });

    $("#start").change(function () {
        var start = moment($("#start").val());
        var hours = $("#duration").val();
        hours = (hours) ? hours : 1;
        //console.log(hours);
        var end = start.add(hours, 'hours');
        $("#end").val(end.format('YYYY-MM-DD[T]HH:mm'));
    });

    $("#end").change(function () {
        var start = moment($("#start").val());
        var end = moment($("#end").val());
        $("#duration").val(moment.duration(end.diff(start)).asHours());
    });

    $("#start").change(function () {
        var start = moment($("#start").val());
        var hours = $("#duration").val();
        hours = (hours) ? hours : 1;
        //console.log(hours);
        var end = start.add(hours, 'hours');
        $("#end").val(end.format('YYYY-MM-DD[T]HH:mm'));
    });

    $("#setNotify").change(function () {
        var checked = ($(this).prop('checked'));
        if (checked)
            $("#notify_set").show(350, 'swing');
        else
            $("#notify_set").hide(200, 'linear');
        //alert(checked);
    });


    function displayMessage(message) {
        $(".response").html("<div class='success p-3 bg-warning' > " + message + "</div>");
        setInterval(function () {
            $(".success").fadeOut();
        }, 1000);
    }

    function clearForm() {
        $("#eventid").val('');
        $("#txtTitle").val('');
        $("#descript").val('');
        $("#start").val('');
        $("#end").val('');
        // $("#event_place").val('');
        // $("#tags").val('');
        // $("#buildobjid").val('');
        // $("#initusername").html('');
    }

    function pad(num, size) {
        var s = "000000000" + num;
        return s.substr(s.length - size);
    }


    $(".smr_pcnt").change(function () {
        //alert($(this).val());

        var totpcnt = parseFloat($("input[name=material_smr_pcnt]").val())
            + parseFloat($("input[name=machine_smr_pcnt]").val())
            + parseFloat($("input[name=fot_smr_pcnt]").val())
            + parseFloat($("input[name=nakl_sp_smr_pcnt]").val());

        if (totpcnt > 100) {
            $(this).val(($(this).val() - (totpcnt - 100)).toFixed(2));
            totpcnt = 100;
        }

        $("#tot_pcnt").val(totpcnt.toFixed(2));

        if (totpcnt == 100)
            $("#tot_pcnt").addClass('text-success font-weight-bold')
        else
            $("#tot_pcnt").removeClass('text-success font-weight-bold');

    });

})
;
