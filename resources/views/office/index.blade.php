@extends('layouts.app')

@section('content')
    <?php
    $request = request();
    $tab = $request->get('tab');
    $tab = is_null($tab) ? '#nsi-info' : '#' . $tab;
    //    var_dump($tab);
    $tabs = array(
//        ['name' => 'Фиск. документы', 'id' => 'nsi-fsd-tab', 'href' => '#nsi-fsd'],
//        ['name' => 'Справочники', 'id' => 'nsi-dic-tab', 'href' => '#nsi-dic'],
//        ['name' => 'Разное', 'id' => 'nsi-aux-tab', 'href' => '#nsi-aux'],
//        ['name' => 'Admin', 'id' => 'nsi-plan-tab', 'href' => '#nsi-plan'],
		);
//    $tabs = [];

    if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id, 'orgstaff.read'))
        $tabs[] = ['name' => 'Контакты', 'id' => 'nsi-info-tab', 'href' => '#nsi-info'];

    if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id, 'meetings.read'))
        $tabs[] = ['name' => 'Совещания', 'id' => 'nsi-meet-tab', 'href' => '#nsi-meet'];


    ?>
	<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

	<script type="text/javascript">
        $(document).ready(function () {
            //alert(123);
            $('#nsi-tabs a[href="{{$tab}}"]').tab('show')
        });

	</script>
	<div class="container">
		<div class="row">
			<div class="col-md-2">

				<div class="nav flex-column nav-pills justify-content-center"
					 id="nsi-tabs"
					 role="tablist"
					 aria-orientation="vertical">
					@foreach($tabs as $tb)

						<a class="nav-link" id="{{$tb['id']}}"
						   data-toggle="pill" href="{{$tb['href']}}"
						   role="tab"
						>{{$tb['name']}}
						</a>
					@endforeach
				</div>
			</div>

			<div class="col-md-10">
				<main class="py-4">

					<div class="tab-content" id="nsi-tabContent">

						<!-- @includeIf('planning.tab_fsd') -->
						@includeIf('office.tab_info')
						@includeIf('office.tab_meet')

					</div>
				</main>
			</div>
		</div>
	</div>

@endsection
