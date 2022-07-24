@extends('layouts.app')

@section('content')
    <?php
    $request = request();
    $tab = $request->get('tab');
    $tab = is_null($tab) ? '#nsi-rqsts' : '#' . $tab;
    //    var_dump($tab);
    $tabs = array(
//        ['name' => 'Фиск. документы', 'id' => 'nsi-fsd-tab', 'href' => '#nsi-fsd'],
//        ['name' => 'Справочники', 'id' => 'nsi-dic-tab', 'href' => '#nsi-dic'],
//        ['name' => 'Разное', 'id' => 'nsi-aux-tab', 'href' => '#nsi-aux'],
//        ['name' => 'Admin', 'id' => 'nsi-plan-tab', 'href' => '#nsi-plan'],
		);
//    $tabs = [];

    if (1==1 or \App\usrsysright::isUserHasRightByCode(\Auth::user()->id, 'mchnrqsts.read'))
        $tabs[] = ['name' => 'Учетчик №1', 'id' => 'nsi-rqsts-tab', 'href' => '#nsi-rqsts'];

    if (1==1 or \App\usrsysright::isUserHasRightByCode(\Auth::user()->id, 'paydocs.read'))
        $tabs[] = ['name' => 'Взаиморасчеты', 'id' => 'nsi-pays-tab', 'href' => '#nsi-pays'];

    if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id, 'wrhdocs.read'))
        $tabs[] = ['name' => 'Производство', 'id' => 'nsi-prod-tab', 'href' => '#nsi-prods'];

    if (1==1 or \App\usrsysright::isUserHasRightByCode(\Auth::user()->id, 'tasks.read'))
        $tabs[] = ['name' => 'Планирование', 'id' => 'nsi-plan-tab', 'href' => '#nsi-plans'];

    //    if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id, 'equiprqsts.read'))
//        $tabs[] = ['name' => 'Материалы и оборудование', 'id' => 'nsi-meet-tab', 'href' => '#nsi-meet'];


    ?>
	<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

	<script type="text/javascript">
        $(document).ready(function () {
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

						@includeIf('rqsts.tab_rqsts')
						@includeIf('rqsts.tab_pays')
                        @includeIf('rqsts.tab_prods')
                        @includeIf('rqsts.tab_plans')
{{--						@includeIf('rqsts.tab_atp')--}}
{{--						@includeIf('rqsts.tab_equip')--}}

					</div>
				</main>
			</div>
		</div>
	</div>

@endsection
