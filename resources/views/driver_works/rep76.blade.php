@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 76;
//$retURL = route('admin') . '#nsi-rep';
//$retURL = '/admin#nsi-rep';
$retURL = route('reports.pub_index');

$report = \App\report::find($thisObjId);

if (!isset($report))
    return redirect($retURL);

$thisTitle = $report->title ?? $report->name;
$action_url = route('reports.rep' . $thisObjId);

?>
@section('title')
    {{$thisTitle}}
@endsection

@section('content')

    <style>
        @media print {
            @page {
                size: A4 landscape;
                /*size: portrait;*/
            //size:auto;   /* auto is the initial value */
                /* this affects the margin in the printer settings */
                margin: 5mm 5mm 5mm 5mm;
            }

            .pagebreak {
                page-break-inside: avoid;
                page-break-before: always;
            }
        }

        body {
            /* this affects the margin on the content before sending to printer */
            margin: 0px;
        }

        .rep-data td {
            /*padding: 5px;*/
            border-collapse: collapse;
            border: 1px solid #e2e2e2;
        }

        .page {
            background-color: white;
        }

        .day {
            text-align: center;
        }

        .work {
            background-color: #e5ffbe;
            font-size: 9px;
            text-align: right;

        }

        .totSum {
            background-color: white;
            font-weight: bold;
            font-size: 1.1em;
        }

    </style>

    <div class="container">

        <div class="row mb-3">
            <div class="col-md-12">

                <div class="params no-print card mt-3 d-print-none">
                    <div class="card-header font-weight-bold">
                        Параметры отчета "{{$thisTitle}}"
                    </div>
                    <div class="card-body">

                        <form name="forRep01" id="forRep01" method="post"
                              action="{{ $action_url }}">
                            @csrf

                            @if(1==1)
                                <div class="row">

                                    <div class="form-group col-md-2">
                                        <label for="s_begdate" class="required">Начало периода:</label>
                                        <input type="date" class="form-control text-center"
                                               name="s_begdate"
                                               value="{{$search_params['s_begdate']??''}}"
                                               required/>
                                    </div>

                                    <div class="form-group col-md-2">
                                        <label for="s_begdate" class="required">Окончание периода:</label>
                                        <input type="date" class="form-control text-center"
                                               name="s_enddate"
                                               value="{{$search_params['s_enddate']??''}}"
                                               required/>
                                    </div>
                                    @if(1==1)
                                        <div class="form-group col-md-3">
                                            <label for="s_ownorgid" class="">Организация:</label>
                                            {!! Form::select('s_ownorgid', $data->ownorgs, $search_params['s_ownorgid'],
                                                            [
                                                            'class' => 'form-control',
                                                            'placeholder' => '-все-',
                                                            ])
                                                            !!}
                                        </div>
                                    @endif
                                    @if(1==1)
                                        <div class="form-group col-md-3">
                                            <label for="s_mchntypeid" class="">Тип:</label>
                                            {!! Form::select('s_mchntypeid', $data->mchntypes, $search_params['s_mchntypeid'],
                                                            [
                                                            'class' => 'form-control',
                                                            'placeholder' => '-все-',
                                                            ])
                                                            !!}
                                        </div>
                                    @endif
                                        <?php
                                        $s_mchntypeid = $search_params['s_mchntypeid'] ?? '';
                                        ?>
                                </div>
                            @endif

                            <div style="border-top:1px solid silver;" class="mt-1 p-1">
                                <button type="submit" class="btn btn-sm btn-success"
                                        formmethod="post">
                                    <i class="fa fa-refresh" aria-hidden="true"></i>
                                    Сформировать
                                </button>
                                <a class="btn btn-close btn-light btn-sm"
                                   href="{{ $retURL  }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if(1==1)
                                    <span class="small float-right" ml-2>
									 <a href="{{route('objevntlog',['sysobjid'=>$thisSysObjId, 'objid'=>$thisObjId,'route'=>Route::current()->getName()])}}">журнал</a>
								</span>
                                @endif

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if (isset($recs))
            @if (count($recs)==0)

                <div class="page p-3 d-print-none" align="center">
                    нет операций для заданных значений
                </div>

            @else
                    <?php
                    $s_period_type = $search_params['s_period_type'] ?? '';
                    $ownorgid = $search_params['s_ownorgid'] ?? '';
                    $s_begdate = $search_params['s_begdate'] ?? '';
                    $s_enddate = $search_params['s_enddate'] ?? '';

                    $month_days = (strtotime($s_enddate) - strtotime($s_begdate)) / 3600 / 24 + 1;
                    //dd($month_days);

                    $base_pre_date = date_create($s_begdate)->sub(new DateInterval('P1D'))->format('Y-m-d');
                    //dd($pre_date);
                    ?>

                <div class="page p-2 container-fluid">

                    <span class="float-right d-print-none">
                        <a class="btn btn-warning btn-sm print-window d-print-none "
                           onclick="window.print();"
                           title="печать">
                            <i class="fa fa-print" aria-hidden="true"></i>
                        </a>
                        @if(1==0)
                            <a class="btn btn-success btn-sm mr-3"
                               href="{{ route('reports.rep'.$thisObjId)  }}?xls=1" title="Выгрузить результаты в Excel">
                                        <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                                    </a>
                        @endif

                        <a class="btn btn-close btn-info btn-sm d-print-none"
                           href="{{ $retURL  }}"><i class="fa fa-times" aria-hidden="true"></i></a>

                    </span>

                    <div class="mt-2" align="center"
                         style="font-size: 14px;">
                        <h4>{{$thisTitle}}</h4>
                        <div class="subtitle" align="center">
                            {!!$data->sub_title!!}
                        </div>
                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>
                    </div>

                    {{-- ------------------------------------------------------------------------------------------}}

                    <table class="table table-bordered table-sm table-data" border="0" style="background-color: white;">
                        <tr>
                            <td rowspan="1" class="small text-right">#пп</td>
                            <td rowspan="1">Авто</td>
                            <td class="text-center small">Период работы</td>
                            <td class="text-center small">Рабочих дней</td>
                            <td class="text-center small">Рабочих дней с пробегом</td>
                            <td rowspan="1" class="text-center small">Пробег, км</td>
                            <td class="text-center small">Средний пробег, км</td>
                            <td rowspan="1" class="text-center small">Заправлено, л</td>
                            <td rowspan="1" class="text-center small">Расход на 100 км, л</td>
                        </tr>
                            <?php
                            $npp = 0;
                            $totMeterQty = $totFuelQty = 0;
                            $meterWD_style = 'background-color:#ffe0e0;';
                            ?>
                        @foreach($recs as $itm)
                                <?php
                                $avg_day_meter = 0;
                                $fuel_per_100 = 0;
                                if ($itm->meter_wrkdays > 0) {
                                    $avg_day_meter = $itm->meter_qty / $itm->meter_wrkdays;
                                    $fuel_per_100 = $itm->fuel_qty / $itm->meter_qty * 100;
                                }
                                $meterWD_style = 'background-color:#ffe0e0;';
                                if ($itm->meter_wrkdays == $itm->wrkdays)
                                    $meterWD_style = 'background-color:#e0ffe0;';
                                ?>
                            <tr>
                                <td class="small text-right">{{++$npp}}</td>
                                <td><a href="{{route('machines.edit',$itm->machineid)}}"
                                       target="_blank">{{$itm->mchn_name}}</a>, <span
                                        class="small ml-2">{{$itm->mchntype_name}}, {{$itm->org_name}}</span>
                                </td>
                                <td class="text-center small" style="font-size: 10px;">{{$itm->min_wrkdate}}
                                    .. {{$itm->max_wrkdate}}  </td>
                                <td class="text-center small">{{$itm->wrkdays}} </td>
                                <td class="text-center small" style="{{$meterWD_style}}">{{$itm->meter_wrkdays}} </td>
                                <td class="text-right small">{{number_format($itm->meter_qty,0)}}</td>
                                <td class="text-right small">{{number_format($avg_day_meter,1)}}</td>
                                <td class="text-right small">{{number_format($itm->fuel_qty,0)}}</td>
                                <td class="text-right small">{{number_format($fuel_per_100,0)}}</td>
                            </tr>
                                <?php
                                $totMeterQty += $itm->meter_qty;
                                $totFuelQty += $itm->fuel_qty;
                                ?>
                        @endforeach
                            <?php
                            if ($totMeterQty > 0) {
                                $avg_fuel_per_100 = $totFuelQty / $totMeterQty * 100;
                            }
                            ?>
                        <tr>
                            <td colspan="5" class="text-right">Итого:</td>
                            <td class="text-right font-weight-bold">{{number_format($totMeterQty,0)}}</td>
                            <td></td>
                            <td class="text-right font-weight-bold">{{number_format($totFuelQty,0)}}</td>
                            <td class="text-right font-weight-bold">{{number_format($avg_fuel_per_100,0)}}</td>
                        </tr>
                    </table>
                    {{-- ------------------------------------------------------------------------------------------}}

                </div>
            @endif
        @endif

    </div>

    {{--    <script src="{{ asset('js/rep43.js') }}" defer></script>--}}

@endsection
