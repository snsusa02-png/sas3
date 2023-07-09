@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 59;
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

                                    <div class="form-group col-md-2 dpt_1" style="">
                                        <label for="s_month" class="required">Год, месяц:</label>
                                        {!! Form::select('s_year_month', $data->yms??[], $search_params['s_year_month']??'',
                                                        [
                                                        'id' => 's_ym',
                                                        'class' => 'form-control',
                                                        'placeholder' => '-укажите-',
                                                        ])
                                                        !!}
                                    </div>


                                    @if(1==0)
                                        <div class="form-group col-md-2 dpt_1" style="">
                                            <label for="s_month" class="required">Месяц:</label>
                                            {!! Form::select('s_month', $data->monthes??[], $search_params['s_month'],
                                                            [
                                                            'id' => 's_month',
                                                            'class' => 'form-control',
                                                            'placeholder' => '-укажите-',
                                                            ])
                                                            !!}
                                        </div>

                                        <div class="form-group col-md-2 dpt_3 " style="">
                                            <label for="s_year" class="required">Год:</label>
                                            {!! Form::select('s_year', $data->years??[], $search_params['s_year'],
                                                            [
                                                            'id' => 's_year',
                                                            'class' => 'form-control',
                                                            'placeholder' => '-укажите-',
                                                            ])
                                                            !!}
                                        </div>
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

                                    <?php
                                    $s_orgname = $search_params['s_orgname'] ?? '';
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
                               href="{{ route('reports.rep43_excel')  }}" title="Выгрузить результаты в Excel">
                                        <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                                    </a>
                        @endif
                        <a class="btn btn-close btn-info btn-sm d-print-none"
                           href="{{ $retURL  }}"><i class="fa fa-times" aria-hidden="true"></i></a>

                    </span>

                    <div class="font-weight-bold mt-2" align="center"
                         style="font-size: 14px;">
                        <h4>{{$thisTitle}} {{$data->ownorgs[$search_params['s_ownorgid']]??''}}</h4>
                        {{$data->monthes[$search_params['s_month']]??''}} {{$search_params['s_year']??''}}
                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>
                    </div>

                    {{-- ------------------------------------------------------------------------------------------}}

                    <table class="table table-bordered table-sm table-data" border="0" style="background-color: white">
                        <tr>
                            <td rowspan="1" class="small text-right">#пп</td>
                            <td rowspan="1">Авто</td>
                            <td class="text-center">Тип занятия</td>
                            <td class="text-center">День, час</td>
                            <td class="text-center">Ночь, час</td>
                        </tr>
                        <?php
                        $max_hrs = $data->days * 12;
                        $npp = 0;
                        $totSum = 0;
                        $totDayHrs = 0;
                        $totNightHrs = 0;
                        $cur_machineid = -1;
                        ?>
                        @foreach($recs as $itm)
                            @if($itm->machineid <> $cur_machineid)
                                @if( $cur_machineid <> -1)
                                    <tr>
                                        <td colspan="3" class="text-right font-weight-bold">Итого по авто
                                            "{{$cur_machine_name}}":
                                        </td>
                                        <td class="text-right font-weight-bold">{{number_format($mchn_DayHrs,2)}}
                                            <div  class="small">{{number_format($mchn_DayHrs/($data->days*13)*100, 1)}}%</div>
                                        </td>
                                        <td class="text-right font-weight-bold">{{number_format($mchn_NightHrs,2)}}
                                            <div class="small">{{number_format($mchn_NightHrs/($data->days*11)*100, 1)}}%</div>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="small text-right">{{++$npp}}</td>
                                    <td colspan="3" class="font-weight-bold font-italic">
                                        <a href="{{route('machines.edit',$itm->machineid)}}"
                                           target="_blank">{{$itm->machine_name}}</a></td>
                                </tr>
                                <?php
                                $cur_machineid = $itm->machineid;
                                $cur_machine_name = $itm->machine_name;
                                $mchn_DayHrs = 0;
                                $mchn_NightHrs = 0;
                                ?>
                            @endif
                            @if ($itm->day_hrs + $itm->night_hrs > 0)
                                <tr>
                                    <td colspan="2"></td>
                                    <td class="text-right">{{$itm->wt_name}}</td>
                                    <td class="text-right">{{number_format($itm->day_hrs,2)}}</td>
                                    <td class="text-right">{{number_format($itm->night_hrs,2)}}</td>
                                </tr>
                            @endif
                            <?php
                            $mchn_DayHrs += $itm->day_hrs;
                            $mchn_NightHrs += $itm->night_hrs;
                            $totDayHrs += $itm->day_hrs;
                            $totNightHrs += $itm->night_hrs;
                            ?>
                        @endforeach

                        @if( $cur_machineid <> -1)
                            <tr>
                                <td colspan="3" class="text-right font-weight-bold">Итого по авто
                                    "{{$cur_machine_name}}":
                                </td>
                                <td class="text-right font-weight-bold">{{number_format($mchn_DayHrs,2)}}
                                    <div  class="small">{{number_format($mchn_DayHrs/($data->days*13)*100, 1)}}%</div>
                                </td>
                                <td class="text-right font-weight-bold">{{number_format($mchn_NightHrs,2)}}
                                    <div class="small">{{number_format($mchn_NightHrs/($data->days*11)*100, 1)}}%</div>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="3" class="text-right">Всего:</td>
                            <td class="text-right font-weight-bold">{{number_format($totDayHrs,2)}}
                                <div  class="small">{{number_format($totDayHrs/$npp/($data->days*13)*100, 1)}}%</div></td>
                            <td class="text-right font-weight-bold">{{number_format($totNightHrs,2)}}
                                <div  class="small">{{number_format($totNightHrs/$npp/($data->days*11)*100, 1)}}%</div></td>
                        </tr>
                    </table>
                    {{-- ------------------------------------------------------------------------------------------}}

                </div>
            @endif
        @endif

    </div>

    {{--    <script src="{{ asset('js/rep43.js') }}" defer></script>--}}

@endsection
