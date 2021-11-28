@extends('layouts.report')
@section('content')

    <?php
    $thisTitle = "Акты выполненных работ по договорам перевозки грузов";
    $thisSysObjID = 855;    //reports
    $thisObjID = 9;
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

		@media print {
			.pagebreak {
				page-break-after: always;
			}
		}

	</style>
	<div class="container">

		<div class="row mb-3">
			<div class="col-md-9">

				<div class="params no-print card mt-3 d-print-none">
					<div class="card-header font-weight-bold">
						Параметры отчета "{{$thisTitle}}"
					</div>
					<div class="card-body">

						<form name="forRep02" id="forRep02" method="post"
							  action="{{ route('reports.rep9') }}">
							@csrf

							<div class="row">
								<div class="form-group col-md-6">
									<label for="name">Заказчик:</label>
									{!! Form::select('s_ownorgid', $ownorgs, $search_params['s_ownorgid']??''
												,['placeholder' => '- укажите компанию-',
												'class' => 'form-control text-center',
												'required' => 'required',
												]) !!}
								</div>

								<div class="form-group col-md-6">
									<label for="name">Исполнитель:</label>
									{!! Form::select('s_driverorgid', $driverorgs, $search_params['s_driverorgid']??''
												,['placeholder' => '- укажите компанию-',
												'class' => 'form-control text-center',
												]) !!}
								</div>
							</div>

							<div class="row">
								<div class="form-group col-md-3">
									<label for="s_begdate">Начало периода:</label>
									<input type="date" class="form-control text-center"
										   name="s_begdate"
										   value="{{old('s_begdate',$search_params['s_begdate']??'')}}"
										   required/>
								</div>

								<div class="form-group col-md-3">
									<label for="s_begdate">Окончание периода:</label>
									<input type="date" class="form-control text-center"
										   name="s_enddate"
										   value="{{old('s_enddate',$search_params['s_enddate']??'')}}"
										   required/>
								</div>

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
								@if(isset($recs))
									<a class="btn btn-warning btn-sm print-window"
									   onclick="window.print();"
									   title="печать">
										<i class="fa fa-print" aria-hidden="true"></i>
									</a>
								@endif
								<span class="small" ml-2>
									<!-- <a href="{{route('objevntlog',['sysobjid'=>15, 'objid'=>82,'route'=>Route::current()->getName()])}}">журнал</a> -->
								</span>


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
				<style>
					.tbl td {
						border: 1px solid black;
						border-collapse: collapse;
						padding: 3px;
					}
				</style>
				@foreach($recs as $rec)
                    <?php
                    $begdate = date_create($search_params['s_begdate']);
                    $enddate = date_create($search_params['s_enddate']);

                    $ym1 = date_format($begdate, 'Y-m');
                    $ym2 = date_format($enddate, 'Y-m');
                    if (1==0 and $ym1 == $ym2)
                        $period = 'с ' . date_format($begdate, 'd') . ' по ' . date_format($enddate, 'd')
                            . '.' . date_format($enddate, 'm.Y');
                    else
                        $period = 'с ' . date_format($begdate, 'd.m.Y') . ' по ' . date_format($enddate, 'd.m.Y');

                    ?>
					<div class="page p-2 pagebreak" style="width:1000px;">
						<!-- -------------------------------------------------------------->

						<table style="width: 100%; ">
							<tr>
								<td>
									<div style="border-bottom: 2px solid black;">
										Акт №{{$rec->docnum}}
										от {{date_format($enddate,'d.m.Y')}}г.
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<table>
										<tr>
											<td>Исполнитель:</td>
											<td>{{$rec->driverorgname}}, ИНН {{$rec->driverorg_inn}}
												, {{$rec->driverorg_address}}</td>
										</tr>
										<tr>
											<td>Заказчик:</td>
											<td>{{$rec->orgname}}, ИНН {{$rec->org_inn}}, {{$rec->org_address}}</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td>
									<table class="tbl" style="width:100%;">
										<tr class="text-center">
											<td>№</td>
											<td>Наименование работ, услуг</td>
											<td>Кол-во</td>
											<td>Ед.</td>
											<td>Цена</td>
											<td>Сумма</td>
										</tr>
										<tr>
											<td class="text-center">1</td>
											<td>Услуги по договору перевозки грузов автомобильным транспортом №{{$rec->contractnum}} от
												{{$rec->contractdate}}. За период {{$period}}.
											</td>
											<td class="text-right">{{number_format($rec->fct_qty,0)}}</td>
											<td class="text-center">час</td>
											<td class="text-right">{{number_format($rec->fct_sum/$rec->fct_qty,2)}}</td>
											<td class="text-right">{{number_format($rec->fct_sum,2)}}</td>
										</tr>
										<tr style="border:none">
											<td colspan="5" class="text-right" style="border: none;">Итого:</td>
											<td class="text-right font-weight-bold"
												style="border: none;">{{number_format($rec->fct_sum,2)}}</td>
										</tr>
										<tr class="border:none">
											<td colspan="5" class="text-right" style="border: none;">Без НДС</td>
											<td class="text-right font-weight-bold" style="border: none;"></td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td>
									Всего оказано услуг 1, на сумму {{number_format($rec->fct_sum,2)}}
									<Br>{{str_price($rec->fct_sum)}}
									<br><br>Вышеперечисленные услуги выполнены полностью и в срок. Заказчик претензий по
									объему, качеству и срокам оказания услуг не имеет.
									<br><br>
								</td>
							</tr>
							<tr>
								<td>
									<table style="width:100%">
										<tr>
											<td width="46%">ИСПОЛНИТЕЛЬ</td>
											<td style="width:60px"></td>
											<td width="46%">ЗАКАЗЧИК</td>
										</tr>
										<tr>
											<td>{{$rec->driverorg_boss_postname}} {{$rec->driverorgname}}</td>
											<td></td>
											<td>{{$rec->org_boss_postname}} {{$rec->orgname}}</td>
										</tr>
										<tr>
											<td style="border-bottom:1px solid black;"><br></td>
											<td></td>
											<td style="border-bottom:1px solid black;"></td>
										</tr>
										<tr class="text-center">
											<td>{{$rec->driverorg_boss_name}}</td>
											<td></td>
											<td>{{$rec->org_boss_name}}</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>

						<!-- -------------------------------------------------------------->
					</div>
				@endforeach
			@endif
		@endif
	</div>
@endsection
