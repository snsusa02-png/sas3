$(document).ready(function () {
});

document.addEventListener('DOMContentLoaded', function () {


    //console.log(SITEURL);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // var curUserID = '{{$data->userid}}';
    // var curUsers = {{ $data->users_id  }};
    // var curUserName = '{{$data->username}}';


    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        //themeSystem: 'bootstrap',
        themeSystem: 'standard',
        //height: 600,
        //initialDate: '2020-06-12',
        navLinks: true, // can click day/week names to navigate views
        locales: 'ru',
        firstDay: 1,
        buttonText: {
            today: 'сегодня',
            month: 'месяц',
            week: 'неделя',
            day: 'день',
            list: 'список'
        },
        events: '/fullcalendar/get?' + auxParams,
        //events: SITEURL + "fullcalendar",
        // events: [
        //     {
        //         id: 'a809',
        //         title: 'my event',
        //         start: '2020-06-01'
        //     }
        // ],
        editable: true,
        dayMaxEvents: true, // allow "more" link when too many events
        displayEventEnd: true,
        selectable: true,
        selectMirror: true,
        //allDay: false,
        select: function (arg) {

            var start = moment(arg.start).format("YYYY-MM-DD HH:mm:ss");
            var end = moment(arg.end).format("YYYY-MM-DD HH:mm:ss");

            //sessionStorage.setItem('start_date', start);	//для последующего использоваания при сохранении формы
            //sessionStorage.setItem('end_date', end);

            //особый формат для поля типа datetime-local
            $("#start").val(moment(arg.start).format("YYYY-MM-DDTHH:mm:ss"));
            $("#end").val(moment(arg.end).format("YYYY-MM-DDTHH:mm:ss"));
            //$("#public").prop('checked', true);
            $("#public_lvl").val(2);
            $(".modal-title").html("Новое событие");
            $("#initusername").html(curUserName);
            $("#regusername").html(curUserName);

            $("#userid").show();
            $("#initusername").hide();

            $("#btnBook").html('Добавить').removeClass('btn-primary').addClass('btn-success');
            $("#btnBook").show();
            $("#btnDelete").hide();
            $(".pl1").show();
            $("#div_src_lnk").hide();
            $("#src_lnk").prop('href', '#');

            //$("#btnModal").trigger("click"); //ассинхронно
            $("#myModal").modal('show')

            //var title = prompt('Описание события:');
            if (1 == 0) {

                $.ajax({
                    url: "/fullcalendar/create",
                    data: 'title=' + title + '&start=' + start + '&end=' + end,
                    type: "POST",
                    success: function (data) {
                        //console.log(data);
                        displayMessage("Успешно добавлено");
                    }
                });

                calendar.addEvent({
                    //id: id,
                    title: title,
                    start: arg.start,
                    end: arg.end,
                    allDay: arg.allDay
                }, true)

                // calendar.fullCalendar('renderEvent',
                //     {
                //         title: title,
                //         start: start,
                //         end: end,
                //         allDay: arg.allDay
                //     },
                //     true
                // );

            }
            calendar.unselect()
        },

        eventAllow: function (dropLocation, draggedEvent) {
            //для чужих событий нет id
            //alert(draggedEvent.extendedProps.ed);
            if (draggedEvent.extendedProps.ed == 1) {
                //return dropLocation.start.isAfter('2016-01-01'); // a boolean
                return true;
            } else {
                return false; // disallow
            }
        },

        //eventDrop: function (event, delta) {
        eventDrop: function (info) {
            //alert(info.event.title + " was dropped on " + info.event.start.toISOString());

            event = info.event;
            //console.log(event,delta);

            var start = moment(event.start).format("YYYY-MM-DD HH:mm:ss");
            var end = (event.end) ? event.end : event.start;
            var end = moment(end).format("YYYY-MM-DD HH:mm:ss");
            $.ajax({
                url: SITEURL + 'fullcalendar/move',
                data: 'title=' + event.title + '&start=' + start + '&end=' + end + '&id=' + event.id,
                type: "POST",
                success: function (response) {
                    displayMessage("Успешно обновлено");
                }
            });
        },

        eventClick: function (arg) {
            // console.log(arg);
            // console.log(arg.event.id);
            // console.log(arg.event.title);

            //Заполним поля модальной формы значениями из события
            $("#eventid").val(arg.event.id);
            $("#txtTitle").val(arg.event.title);
            $("#start").val(moment(arg.event.start).format("YYYY-MM-DDTHH:mm:ss"));
            $("#end").val(moment(arg.event.end).format("YYYY-MM-DDTHH:mm:ss"));
            $("#event_place").val(arg.event.extendedProps.event_place).show();
            $("#color").val(arg.event.backgroundColor).show();
            //console.log(arg.event.extendedProps)
            // console.log(arg.event.backgroundColor)
            $("#userid").val(arg.event.extendedProps.inituserid);
            $("#initusername").html(arg.event.extendedProps.initusername);
            $("#regusername").html(arg.event.extendedProps.regusername);
            // $("#public").prop('checked', (arg.event.extendedProps.public == 1));
            $("#public_lvl").val(arg.event.extendedProps.public_lvl).show();
            $("#descript").val(arg.event.extendedProps.descript).show();
            $("#tags").val(arg.event.extendedProps.tags).show();
            $("#buildobjid").val(arg.event.extendedProps.buildobjid).show();
            $("#fulledit_lnk").prop('href', '#');
            $("#btnBook").html('Сохранить').removeClass('btn-success').addClass('btn-primary');

            //Заполним слайдеры уведомления
            var notify_before = arg.event.extendedProps.notify_before;
            if (!notify_before) {
                $("#notifyDays").val('');
                $("#notifyHours").val('');
                $("#notifyMinutes").val('');

                $("#setNotify").prop('checked', false);
                $("#notify_set").hide();

            } else {
                arr = notify_before.split(' ');
                $("#notifyDays").val(arr[0]);
                arr = arr[1].split(':');
                $("#notifyHours").val(arr[0]);
                $("#notifyMinutes").val(arr[1]);

                $("#setNotify").prop('checked', true);
                $("#notify_set").show();
            }

            $(".modal-title").html("Событие");

            if (arg.event.extendedProps.src_url) {
                $("#src_lnk").prop('href', arg.event.extendedProps.src_url);
                $("#div_src_lnk").show();
            } else {
                $("#div_src_lnk").hide();
                $("#src_lnk").prop('href', '#');
            }

            //ссылку на  полную карточку события показываем всем, кто в "теме"
            $("#fulledit_lnk").prop('href', '/events/' + arg.event.id + '/edit?returl=/events');
            $("#fulledit_lnk").show();

            // if (arg.event.extendedProps.inituserid == curUserID) {
            //console.log(curUsers.indexOf(arg.event.extendedProps.inituserid));
            //если событие "свое" или одного из ассистируемых пользователей, то даем сохранить/удалить
            if (curUsers.indexOf(arg.event.extendedProps.inituserid) > -1) {
                $(".pl1").show();
                $("#btnBook").show();
                $("#btnDelete").show();
                $("#userid").show();
                $("#initusername").hide();
            } else {
                $("#btnBook").hide();
                $("#btnDelete").hide();
                $("#userid").hide();
                $("#initusername").show();

                $(".pl1").show();
                if (arg.event.extendedProps.public_lvl == 1) {
                    $(".pl1").hide();
                    $("#txtTitle").val('- дело -');
                    $("#event_place").val('');
                    $("#color");
                    $("#public_lvl").val('');
                    $("#descript").val('');
                    $("#tags").val('');
                    $("#buildobjid").val('').hide();
                    $("#fulledit_lnk").prop('href', '#').hide();
                }
            }

            var start = moment($("#start").val());
            var end = moment($("#end").val());
            var duration = moment.duration(end.diff(start));
            //var hours = duration.asHours();
            //hours = (hours) ? hours : 1;
            //console.log(hours);
            $("#duration").val(duration.asHours());
            //console.log(duration.asHours());

            //откроем форму
            $("#myModal").modal('show')
        }
    });

    //отобразить данные
    calendar.render();

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
            var start = $("#start").val();
            var end = $("#end").val();
            var color = $("#color").val();
            var event_place = $("#event_place").val();
            //var public = ($("#public").prop('checked')) ? 1 : 0;
            var public_lvl = $("#public_lvl").val();
            var descript = encodeURIComponent($("#descript").val());
            var tags = $("#tags").val();
            var buildobjid = $("#buildobjid").val();

            var notify_before = '';
            if ($("#setNotify").prop('checked')) {
                notify_before = pad($("#notifyDays").val(), 1) + ' '
                    + pad($("#notifyHours").val(), 2) + ':'
                    + pad($("#notifyMinutes").val(), 2) + ':00';
            }
            //alert(notify_before);


            var url = (eventid) ? "/fullcalendar/update" : "/fullcalendar/create";
            //console.log(url);

            $.ajax({
                url: url,
                data: 'id=' + eventid + '&title=' + txtTitle + '&start=' + start
                    + '&end=' + ((end) ? end : start)
                    + '&event_place=' + event_place
                    + '&userid=' + userid
                    + '&color=' + color
                    + '&public_lvl=' + public_lvl
                    + '&descript=' + descript
                    + '&buildobjid=' + buildobjid
                    + '&notify_before=' + notify_before
                    + '&tags=' + tags,
                type: "POST",
                success: function (data) {
                    //console.log(data);
                    displayMessage("Успешно сохранено");
                }
            });

            //calendar.refetchEvents();
            window.location.reload();
        } else
            alert('Укажите название события!');
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
                url: "/fullcalendar/delete",
                data: 'id=' + eventid,
                type: "POST",
                success: function (data) {
                    //console.log(data);
                    displayMessage('Успешно сохранено');
                }
            });

            //calendar.refetchEvents();
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
        console.log(hours);
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
    $("#event_place").val('');
    $("#tags").val('');
    $("#buildobjid").val('');
    $("#initusername").html('');
}

function pad(num, size) {
    var s = "000000000" + num;
    return s.substr(s.length - size);
}
