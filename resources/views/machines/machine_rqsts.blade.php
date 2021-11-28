@extends('layouts.edit')

@section('content')
	@if (!isset( $machine))
        <?php
        redirect()->route('machines.index');
        header("Location:" . route('machines.index'));
        die();
        ?>
	@else
        <?php
        $mayCreate = \App\usrsysright::isUserHasRightByCode(Auth::user()->id, 'mchnrqsts.create');
        ?>

		<style>
			label {
				color: gray;
				margin-bottom: 0px;
			}
		</style>

		<div class="container">
			<div class="row ">
				@if ($machine->id != -1)
					<div class="offset-md-0 col-md-12">
						<span class="helptags" data="org_extids"/>

						<div class="card mt-2">

							@include('layouts.edit_msgs')

							<div class="card-header">
								Заявки для "<b>{{$machine->name}}</b>"

								<a class="btn btn-close btn-info btn-sm"
								   style="float:right"
								   href="{{ route('machines.edit',$machine->id) }}"
								   title="вернуться в карточку"
								>
									<i class="fa fa-times" aria-hidden="true"></i>
								</a>
							</div>

							<div class="card-body">

								<table class="table">
									<thead>
									<tr>
										<td>#</td>
										<td class="text-center">Когда</td>
										<td class="text-center">Прододжительность, чч:мм</td>
										<td>Описание</td>
										<td class="text-center">Инициатор</td>
										<td style="width:36px; text-align: center;">
											@if($mayCreate)
{{--												<a href="{{ route('mchnrqsts.createformchn', $machine->id)}}?returl={{Request::url()}}"--}}
{{--												<a href="{{ route('mchnrqsts.createformchn', $machine->id)}}"--}}
{{--												<a href="{{ route('mchnrqsts.create', $machine->id)}}"--}}
												<a href="{{ route('mchnrqsts.create')}}?machineid={{$machine->id}}"
												   class="btn btn-warning btn-sm"
												   title="Добавить запись">
													<i class="fa fa-plus"></i>
												</a>
											@endif
										</td>
									</tr>

									</thead>
									<tbody>
                                    <?php
                                    $rec0 = 1;
                                    ?>
									@foreach($recs as $itm)
                                        <?php
                                        $begdt = strtotime($itm->plnbegdt);
                                        $begdt_c = date("Y-m-d,  H:i", $begdt);

                                        $enddt = strtotime($itm->plnenddt);
                                        if (date("Y-m-d", $enddt) == date("Y-m-d", $begdt))
                                            $enddt_c = date("H:i", $enddt);
                                        else
                                            $enddt_c = date("Y-m-d H:i", $enddt);
                                        ?>
										<tr>
											<td class="small text-right">
												{{$loop->index + $rec0}}
											</td>
											<td class="text-left small">
												{{$begdt_c}} - {{$enddt_c}}
											</td>
											<td class="text-center">
												{{mb_substr($itm->duration,0,5)}}
											</td>
											<td>
												{{$itm->tgt_addr}}: {{$itm->descript}}
											</td>
											<td class="text-left small">
												{{$itm->initusername}}
											</td>
											<td style="text-align: center;">
{{--												<a href="{{ route('estdocs.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"--}}
												<a href="{{ route('mchnrqsts.edit',['id'=>$itm->id])}}"
												   class="btn btn-sm btn-primary"
												   title="Просмотреть/Изменить запись">
													<i class="fa fa-pencil"></i>
												</a>
											</td>
										</tr>
									@endforeach
									</tbody>
									<tfoot>
									</tfoot>
								</table>

							</div>
						</div>
					</div>
				@endif

			</div>
		</div>
	@endif

	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<style>
		#chart_div {
			margin: 0 auto;
		}
	</style>

	<div class="container">
		<div class="row">
			<div class="card col-md-10 mt-3" style="margin: 0 auto;">
				<div class="card-header">График занятости для "<b>{{$machine->name}}</b>"</div>
				<div class="card-body">
					<div id="chart_div" align="center" class=""></div>
				</div>
				<div class="card-footer"></div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
        google.charts.load('current', {'packages': ['timeline']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
			{{--var data = google.visualization.arrayToDataTable([--}}
			{{--    ['Activity', 'Описание', 'Start Time', 'End Time'],--}}

			{{--		@foreach($recs as $itm)--}}
			{{--    ['{{$itm->name}}', '{{$itm->descript}}',--}}
			{{--        new Date( {{date("Y, m, d, H, i", strtotime($itm->begdt)) }} ),--}}
			{{--        new Date( {{date("Y, m, d, H, i", strtotime($itm->enddt)) }} )],--}}
			{{--	@endforeach--}}
			{{--]);--}}

            //var chart = new google.visualization.Timeline(container);

            var dataTable = new google.visualization.DataTable();
            dataTable.addColumn({type: 'string', id: 'RowLabel'});
            dataTable.addColumn({type: 'string', id: 'Name'});
            // dataTable.addColumn({ type: 'string', role: 'tooltip' });
            dataTable.addColumn({type: 'date', id: 'Start'});
            dataTable.addColumn({type: 'date', id: 'End'});
            dataTable.addRows([
					@foreach($recs as $itm)
                ['{{$itm->rqsttypename}}', '{{$itm->descript}}',
                    new Date( {{date("Y, m, d, H, i", strtotime($itm->plnbegdt.' -1 month')) }} ),
                    new Date( {{date("Y, m, d, H, i", strtotime($itm->plnenddt.' -1 month')) }} )],
				@endforeach
                // [ 'Magnolia Room',  'CSS Fundamentals',    new Date(0,0,0,12,0,0),  new Date(0,0,0,14,0,0) ],
                // [ 'Magnolia Room',  'Intro JavaScript',    new Date(0,0,0,14,30,0), new Date(0,0,0,16,0,0) ],
                // [ 'Magnolia Room',  'Advanced JavaScript', new Date(0,0,0,16,30,0), new Date(0,0,0,19,0,0) ],
                // [ 'Gladiolus Room', 'Intermediate Perl',   new Date(0,0,0,12,30,0), new Date(0,0,0,14,0,0) ],
                // [ 'Gladiolus Room', 'Advanced Perl',       new Date(0,0,0,14,30,0), new Date(0,0,0,16,0,0) ],
                // [ 'Gladiolus Room', 'Applied Perl',        new Date(0,0,0,16,30,0), new Date(0,0,0,18,0,0) ],
                // [ 'Petunia Room',   'Google Charts',       new Date(0,0,0,12,30,0), new Date(0,0,0,14,0,0) ],
                // [ 'Petunia Room',   'Closure',             new Date(0,0,0,14,30,0), new Date(0,0,0,16,0,0) ],
                // [ 'Petunia Room',   'App Engine',          new Date(0,0,0,16,30,0), new Date(0,0,0,18,30,0) ]

            ]);

            var options = {
                title: 'Rate the Day on a Scale of 1 to 10',
                legend: 'asdasdasdas none',
                // height: 650,
                timeline: {
                    groupByRowLabel: true,
                    showRowLabels: true,
                    //singleColor: '#8d8'
                },
                // backgroundColor: '#ffd'
            };

            var chart = new google.visualization.Timeline(document.getElementById('chart_div'));

            chart.draw(dataTable, options);
        }

	</script>
@endsection
