@extends('layouts.app')

@section('content')
	<meta name="csrf-token" content="{{ csrf_token() }}">

	{{--	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">--}}
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	{{--	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>--}}
	{{--	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>--}}


	<link href='https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/css/bootstrap.css' rel='stylesheet'/>
	<link href='https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.13.1/css/all.css' rel='stylesheet'>

	<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.0.0/main.css' rel='stylesheet'/>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"
			integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>

	<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.0.0/main.min.js'></script>

	<script>
        $(document).ready(function () {
        });

        document.addEventListener('DOMContentLoaded', function () {

            var SITEURL = "{{url('/')}}/";
            //console.log(SITEURL);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var curUserID = '{{$data->userid}}';
            var curUserName = '{{$data->username}}';

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
                events: '/fullcalendar/get',
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
                select: function (arg) {

                    var start = moment(arg.start).format("YYYY-MM-DD HH:mm:ss");
                    var end = moment(arg.end).format("YYYY-MM-DD HH:mm:ss");

                    //sessionStorage.setItem('start_date', start);	//для последующего использоваания при сохранении формы
                    //sessionStorage.setItem('end_date', end);

                    //особый формат для поля типа datetime-local
                    $("#start").val(moment(arg.start).format("YYYY-MM-DDTHH:mm:ss"));
                    $("#end").val(moment(arg.end).format("YYYY-MM-DDTHH:mm:ss"));
                    $("#public").prop('checked', true);
                    $(".modal-title").html("Новое событие");
                    $("#initusername").html(curUserName);
                    $("#btnBook").html('Добавить').removeClass('btn-primary').addClass('btn-success');
                    $("#btnBook").show();
                    $("#btnDelete").hide();

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
                    $("#event_place").val(arg.event.extendedProps.event_place);
                    $("#color").val(arg.event.backgroundColor);
                    console.log(arg.event.extendedProps)
                    // console.log(arg.event.backgroundColor)
                    $("#initusername").html(arg.event.extendedProps.initusername);
                    $("#public").prop('checked', (arg.event.extendedProps.public == 1));
                    $("#descript").val(arg.event.extendedProps.descript);
                    $("#tags").val(arg.event.extendedProps.tags);
                    $("#buildobjid").val(arg.event.extendedProps.buildobjid);
                    $("#fulledit_lnk").prop('href','#');
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

                    //ссылку на  полную карточку события показываем всем, кто в "теме"
                    $("#fulledit_lnk").prop('href','/events/'+arg.event.id+'/edit?returl=/events');
                    $("#fulledit_lnk").show();

                    if (arg.event.extendedProps.inituserid == curUserID) {
                        $("#btnBook").show();
                        $("#btnDelete").show();
                    } else {
                        $("#btnBook").hide();
                        $("#btnDelete").hide();
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
                    txtTitle=encodeURIComponent(txtTitle);
                    var eventid = $("#eventid").val();
                    //console.log(eventid)
                    //start = sessionStorage.getItem('start_date');
                    //end = sessionStorage.getItem('end_date');
                    allDay = false;
                    var start = $("#start").val();
                    var end = $("#end").val();
                    var color = $("#color").val();
                    var event_place = $("#event_place").val();
                    var public = ($("#public").prop('checked')) ? 1 : 0;
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
                            + '&color=' + color
                            + '&public=' + public
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
                }
                $("#myModal").modal('hide');
                clearForm();
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
                console.log(hours);
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
	</script>

	<style>

		body {
			margin: 40px 10px;
			padding: 0;
			font-family: Arial, Helvetica Neue, Helvetica, sans -serif;
			font-size: 14px;
		}

		.container {
			background-color: white;
		}

		#calendar {
			background-color: white;
			max-width: 1100px;
			margin: 0 auto;
		}

	</style>
	</head>

	<body>
	<div class="container">

        <?php
        $breadcrumbs = [
            'Планирование' => route('planning'),
            'События' => route('events.index'),
            'Календарь' => null,
        ];
        //dd($breadcrumbs);
        ?>
		@includeIf('layouts.breadcrumbs')


		<div class="response float-right" style="position: absolute;z-index: 1"></div>

		<div style="max-height: 600px">
			<div id='calendar'></div>
		</div>

		<!-- The Modal -->
		<div class="modal fade" id="myModal">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">

					<!-- Modal Header -->
					<div class="modal-header" style="background-color: #3a7ab1;
    color: snow;">
						<h5 class="modal-title">Modal Heading</h5>
						<button type="button" class="close modal-close" data-dismiss="modal">&times;</button>
					</div>

					<!-- Modal body -->
					<div class="modal-body" style="background-color: aliceblue">
						<form id="form">
							<input type="hidden" id="eventid">
							<input type="hidden" id="duration">
							<div class="row">
								<div class="form-group col-md-9">
									<label>Название</label>
									<input type="text" name="txtTitle" id="txtTitle" class="form-control" value=""
										   required maxlength="160">
								</div>
								<div class="form-group col-md-3">
									<label>Инициатор</label>
									<div id="initusername" class="font-weight-bold">{{$data->username}}</div>
								</div>
							</div>

							<div class="row">
								<div class="form-group col-md-5">
									<label>Начало</label>
									<input type="datetime-local" name="start" id="start" class="form-control" required>
								</div>
								<div class="form-group col-md-5">
									<label>Окончание</label>
									<input type="datetime-local" name="end" id="end" class="form-control" required>
								</div>
								<div class="form-group col-md-2">
									<label>Цвет этикетки</label>
									<input type="color" id="color" value="#468db9" class="form-control">
								</div>

							</div>
							<div class="row">
								<div class="form-group col-md-5">
									<label>Место</label>
									<input type="text" id="event_place" class="form-control" value=""
										   required maxlength="160">
								</div>
								<div class="form-group col-md-7">
									<label>Описание</label>
									<textarea id="descript" class="form-control" maxlength="360" rows="3"></textarea>
								</div>
							</div>
							<div class="row">
								<div class="form-group col-md-2">
									<label>Доступно всем</label>
									<input type="checkbox" id="public" class="form-control" value="1">
								</div>
								<div class="form-group offset-md-3 col-md-7">
									<label>Тэги</label>
									<input type="text" id="tags" class="form-control" maxlength="360">
								</div>
							</div>

							<div class="row">
								<div class=" col-md-5">
									<div class="px-3 py-1" style="background-color: #eaf2f9; border-radius: 8px;">
										<label>
											<input type="checkbox" value="1" id="setNotify">
											<i class="fa fa-bell" aria-hidden="true"></i> Предупредить за</label>
										<div class="row " id="notify_set" style="display: none;">
											<div class="form-group col-md-4">
												<label class="small">дней</label>
												<input type="text"
													   class="form-control text-center"
													   min="0" max="9"
													   placeholder="дней"
													   id="notifyDays"
													   onchange="setRangeByInput(this, 'slide_notifyDays')"
												/>
												<div class="slidecontainer">
													<input type="range"
														   min="0" max="9"
														   step="1"
														   class="slider"
														   id="slide_notifyDays"
														   oninput="setInputByRange(this, 'notifyDays')"
													>
												</div>
											</div>
											<div class="form-group col-md-4">
												<label class="small">часов</label>
												<input type="text"
													   class="form-control text-center"
													   min="0" max="23"
													   placeholder="часов"
													   id="notifyHours"
													   onchange="setRangeByInput(this, 'slide_notifyHours')"
												/>


												<div class="slidecontainer">
													<input type="range"
														   min="0" max="23"
														   step="1"
														   class="slider"
														   id="slide_notifyHours"
														   oninput="setInputByRange(this, 'notifyHours')"
													>
												</div>
											</div>
											<div class="form-group col-md-4">
												<label class="small">минут</label>
												<input type="text"
													   class="form-control text-center"
													   min="0" max="59"
													   placeholder="минут"
													   id="notifyMinutes"
													   onchange="setRangeByInput(this, 'slide_notifyMinutes')"
												/>


												<div class="slidecontainer">
													<input type="range"
														   min="0" max="55"
														   step="5"
														   value=""
														   class="slider"
														   id="slide_notifyMinutes"
														   oninput="setInputByRange(this, 'notifyMinutes')"
													>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="form-group offset-md-0 col-md-7">
									<label>Объект</label>
									{!! Form::select('buildobjid', $data->buildobjs, null
			,['placeholder' => '',
			'class' => 'form-control form-control-sm',
			'id' => 'buildobjid',
			]) !!}
								</div>
							</div>
						</form>
					</div>

					<!-- Modal footer -->
					<div class="modal-footer">
							<span class="button btn-sm btn-light fulleditform mr-5">
							<a href="{{route('events.edit',0)}}" id="fulledit_lnk" title="Редактировать в полной форме" style="display: none;">
								<i class="fa fa-pencil-square-o" aria-hidden="true"></i>
							</a>
						</span>

						<button type="button" id="btnBook" class="btn btn-primary">Сохранить</button>
						<button type="button" id="" class="btn btn-light modal-close ml-2" data-dismiss="modal">Закрыть
						</button>
						<button type="button" id="btnDelete" class="btn btn-danger btn-sm ml-4" title="Удалить">
							<i class="fa fa-trash" aria-hidden="true"></i>
						</button>
					</div>

				</div>
			</div>
		</div>
	</div>

	<script src="{{ asset('js/setRangeInput.js') }}" defer></script>

	</body>
@endsection