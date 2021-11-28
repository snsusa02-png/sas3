@extends('layouts.report')
@section('content')

    <?php
    $thisTitle = "График занятости спецтехники и механизмов за период";
    $thisSysIbjId = 855;    //reports
    $thisObjId = 6;
    $retURL = route('reports');
    ?>

	<style>
		.rep-data td {
			padding: 5px;
			border-collapse: collapse;
			border: 1px solid #e2e2e2;
		}

		.page {
			background-color: white;
		}

		.userSum {
			background-color: white;
			font-weight: bold;
			font-size: 1em;
		}

		.mnthSum {
			background-color: white;
			font-weight: bold;
			font-size: 1.0em;
		}

		.totSum {
			background-color: white;
			font-weight: bold;
			font-size: 1.1em;
		}

		.signers {
			width: 90%;
		}
	</style>
	<div class="container00">

		<div class="row mb-3">
			<div class="col-md-6">

				<div class="params no-print card mt-3 d-print-none">
					<div class="card-header font-weight-bold">
						Параметры отчета "{{$thisTitle}}"
					</div>
					<div class="card-body">

						<form name="forRep01" id="forRep01" method="post"
							  action="{{ route('reports.rep6') }}">
							@csrf

							<div class="row">
								<div class="form-group col-md-4">
									<label for="s_begdate">Начало периода<sup style="color:red;">*</sup>:</label>
									<input type="date" class="form-control text-center"
										   name="s_begdate"
										   value="{{$search_params['s_begdate']??''}}"
										   required/>
								</div>

								<div class="form-group col-md-4">
									<label for="s_begdate">Окончание периода<sup style="color:red;">*</sup>:</label>
									<input type="date" class="form-control text-center"
										   name="s_enddate"
										   value="{{$search_params['s_enddate']??''}}"
										   required/>
								</div>

								{{--								<div class="form-group col-md-4">--}}
								{{--									<label for="name">Владелец:</label>--}}
								{{--									{!! Form::select('s_ownorgid', $ownorgs, $search_params['s_ownorgid']??''--}}
								{{--												,['placeholder' => '- все -',--}}
								{{--												'class' => 'form-control text-center',--}}
								{{--												]) !!}--}}
								{{--								</div>--}}

							</div>

							<div style="border-top:1px solid silver;" class="mt-1 p-1">
								<button type="submit" class="btn btn-sm btn-success"
										{{--								formaction="{{ route('mchnrqsts.index') }}"--}}
										formmethod="post">
									<i class="fa fa-refresh" aria-hidden="true"></i>
									Сформировать
								</button>
								<a class="btn btn-close btn-info btn-sm"
								   href="{{ $retURL  }}">
									<i class="fa fa-window-close-o" aria-hidden="true"></i>
									Закрыть
								</a>
								@if(1==1)
									<span class="small float-right" ml-2>
									 <a href="{{route('objevntlog',['sysobjid'=>$thisSysIbjId, 'objid'=>$thisObjId,'route'=>Route::current()->getName()])}}">журнал</a>
								</span>
								@endif


							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		@if (isset($recs))
			@if ($recs->count()==0)

				<div class="page p-3 d-print-none" align="center">
					нет операций для заданных значений
				</div>

			@else
                <?php
                $ownorgid = $search_params['s_ownorgid'] ?? '';
                $s_begdate = $search_params['s_begdate'] ?? '';
                $s_enddate = $search_params['s_enddate'] ?? '';

                ?>


				<div class="page p-2 ">

					{{--					<a class="btn btn-warning btn-sm print-window d-print-none float-right"--}}
					{{--					   onclick="window.print();"--}}
					{{--					   title="печать">--}}
					{{--						<i class="fa fa-print" aria-hidden="true"></i>--}}
					{{--					</a>--}}

					<div class="font-weight-bold mt-2" align="center"
						 style="font-size: 18px;">
						<h3>График занятости</h3>
						спецтехники и механизмов за период
						с {{date_format(date_create($s_begdate),'d.m.Y')}}
						по {{ date_format(date_create($s_enddate),'d.m.Y')}}

						<span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>
					</div>


					<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
					<style>
						#chart_div {
							margin: 0 auto;
						}
					</style>

					<div id="chart_div" align="center" class=""></div>
					<script type="text/javascript">
                        google.charts.load('current', {'packages': ['timeline']});
                        google.charts.setOnLoadCallback(drawChart);

                        function drawChart() {

                            var dataTable = new google.visualization.DataTable();

                            dataTable.addColumn({type: 'string', id: 'RowLabel'});
                            dataTable.addColumn({type: 'string', id: 'Name'});
                            dataTable.addColumn({type: 'string', role: 'tooltip'});
                            dataTable.addColumn({type: 'date', id: 'Start'});
                            dataTable.addColumn({type: 'date', id: 'End'});
                            dataTable.addRows([
									@foreach($recs as $itm)
                                    <?php
                                    $begdt = date_create($itm->fctbegdt);
                                    $enddt = date_create($itm->fctenddt);
                                    $wrkperiod = date_format($begdt, 'd.m.Y H:i');
                                    if (date_format($begdt, 'd.m.Y') == date_format($enddt, 'd.m.Y'))
                                        $wrkperiod .= ' - ' . date_format($enddt, 'H:i');
                                    else
                                        $wrkperiod .= ' - ' . date_format($enddt, 'd.m.Y H:i');
                                    ?>
                                ['{{$itm->asgnmachineid.'. '.$itm->machinename}}', '', '{{$wrkperiod.' '.$itm->driver_orgname. ': '. $itm->drivername }}',
                                    new Date( {{date("Y, m, d, H, i", strtotime($itm->fctbegdt.' -1 month')) }} ),
                                    new Date( {{date("Y, m, d, H, i", strtotime($itm->fctenddt.' -1 month')) }} )],
								@endforeach
                            ]);

                            var options = {
                                title: 'Rate the Day on a Scale of 1 to 10',
                                legend: 'asdasdasdas none',
                                height: 650,
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


				</div>
	@endif
	@endif

@endsection
