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
        var SITEURL = "{{url('/')}}/";
        var curUserID = '{{$data->userid}}';
        var curUsers = [{{ $data->users_id  }}];
        var curUserName = '{{$data->username}}';
        var auxParams = '{!!  $data->aux_params!!}';
	</script>

	<script src="{{ asset('js/taskcalendar.js') }}" defer></script>

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

		<form name="forIndex" id="forIndex" method="put" class="form-inline"
			  action="{{ route('events.calendar') }}">
			@csrf

			<div class="form-group mb-2">
				<label class="mr-sm-2">Для:</label>
				{!! Form::select('s_userid',
				 $data->s_users,
				 $data->search_params['s_userid']??'',
				['class' => 'form-control',
				'placeholder'=>'-все видимое мне-',
				'onchange=submit();']) !!}
			</div>

		</form>

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
								<div class="form-group col-md-8">
									<label class="mb-0">Название:</label>
									<input type="text" name="txtTitle" id="txtTitle" class="form-control" value=""
										   required maxlength="160">
								</div>
								<div class="form-group col-md-4">
									<label class="mb-0">Владелец календаря:</label>
									{!! Form::select('userid', $data->assisted_users, $data->userid,
												 [
												 'id' => 'userid',
												 'class' => 'form-control',
												 'placeholder' => '-выбор-',
												 ]) !!}
									<div id="initusername" class="font-weight-bold">{{$data->username}}</div>
								</div>
							</div>

							<div class="row">
								<div class="form-group col-md-5">
									<label class="mb-0">Начало:</label>
									<input type="datetime-local" name="start" id="start" class="form-control" required>
								</div>
								<div class="form-group col-md-5">
									<label class="mb-0">Окончание:</label>
									<input type="datetime-local" name="end" id="end" class="form-control" required>
								</div>
								<div class="form-group col-md-2">
									<div class="pl1">
										<label>Цвет этикетки</label>
										<input type="color" id="color" value="#468db9" class="form-control">
									</div>
								</div>

							</div>
							<div class="row pl1">
								<div class="form-group col-md-7">
									<div class="pl1">
										<label class="mb-0">Описание:</label>
										<textarea id="descript" class="form-control" maxlength="360"
												  rows="3"></textarea>
									</div>
								</div>

								<div class="form-group col-md-5">
									<div class="pl1">
										<label class="mb-0">Место:</label>
										<input type="text" id="event_place" class="form-control" value=""
											   required maxlength="160">
									</div>
									<div id="div_src_lnk" class="mt-3">

										<div class="pl-0">
											<a href="" id="src_lnk"
											   title="Перейти к первоисточнику"
											   target="_blank">
												<i class="fa fa-pencil-square-o" aria-hidden="true"></i>
												первоисточник
											</a>
										</div>
									</div>
								</div>
							</div>
							<div class="row pl1">
								<div class="form-group offset-md-0 col-md-7">
									<div class="pl1">
										<label class="mb-0">Тэги:</label>
										<input type="text" id="tags" class="form-control" maxlength="360">
									</div>
								</div>

								<div class="form-group col-md-5">
									<div class="pl1">
										<label>Приватность:</label>

										{!! Form::select('public_lvl', $data->public_lvls, null,
											['id' => 'public_lvl',
											'class' => 'form-control',
											'placeholder'=>'-все-'
											]) !!}

									</div>
								</div>
							</div>

							<div class="row pl1">
								<div class="form-group offset-md-0 col-md-7">
									<div class="pl1">
										<label class="mb-0">Объект:</label>
										{!! Form::select('buildobjid', $data->buildobjs, null
											,['placeholder' => '',
											'class' => 'form-control form-control-sm',
											'id' => 'buildobjid',
											]) !!}
									</div>
								</div>

								<div class=" col-md-5">
									<div class="px-3 py-1 pl1" style="background-color: #eaf2f9; border-radius: 8px;">
										<label class="mb-0">
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
							</div>
						</form>
					</div>

					<!-- Modal footer -->
					<div class="modal-footer">
							<span class="mr-4">Регистратор:
						<span id="regusername" class=""></span>
								</span>

						<span class="button btn-sm btn-light fulleditform mr-5">
							<a href="{{route('events.edit',0)}}" id="fulledit_lnk" title="Редактировать в полной форме"
							   style="display: none;">
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
