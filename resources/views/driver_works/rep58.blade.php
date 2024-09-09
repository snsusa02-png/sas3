@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 58;
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

                                    @if(1==0)
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
                            <td rowspan="2" class="small text-right">#пп</td>
                            <td rowspan="2">Работник</td>
                            <td rowspan="2" class="text-center">Раб.<br>дней</td>
                            <td rowspan="1" colspan="2" class="text-center">Работа, час</td>
                            <td rowspan="1" colspan="4" class="text-center">Простой, час</td>
                            <td rowspan="2" class="text-center">Сумма, руб</td>
                        </tr>
                        <tr align="center">
                            <td>День</td>
                            <td>Ночь</td>
                            <td>Всего</td>
                            <td class="small">в т.ч. ремонт</td>
                            <td class="small">в т.ч. сон</td>
                            <td class="small">в т.ч. простой</td>
                        </tr>
                        <?php
                        $npp = 0;
                        $totSum = 0;
                        $totDayWrkHrs = 0;
                        $totNightWrkHrs = 0;
                        $totBrkHrs = 0;
                        $totBrk11Hrs = 0;
                        $totBrk21Hrs = 0;
                        $totBrk22Hrs = 0;
                        $totWrkHrs = 0;
                        ?>
                        @foreach($recs as $itm)
                            <?php
                            $staff_name = $itm->staff_lname;
                            if (isset($itm->staff_fname)) {
                                $staff_name .= ' ' . mb_substr($itm->staff_fname, 0, 1) . '.';
                                if (isset($itm->staff_mname))
                                    $staff_name .= mb_substr($itm->staff_mname, 0, 1) . '.';
                            }
                            $day_hr_rate = $itm->day_hr_rate;
                            $day_hr_rate_min = $itm->day_hr_rate_min;
                            if ($day_hr_rate_min <> $day_hr_rate)
                                $day_hr_rate = $day_hr_rate_min . ' .. ' . $day_hr_rate;

                            $night_hr_rate = $itm->night_hr_rate;
                            $night_hr_rate_min = $itm->night_hr_rate_min;
                            if ($night_hr_rate_min <> $night_hr_rate)
                                $night_hr_rate = $night_hr_rate_min . ' .. ' . $night_hr_rate;
                            ?>
                            <tr>
                                <td class="small text-right">{{++$npp}}</td>
                                <td><a href="{{route('orgstaff.edit',$itm->staffid)}}"
                                       target="_blank">{{$itm->staff_name}}</a>, <span
                                        class="small ml-2"> {{$itm->postname}}</span>

                                <div class="float-right small">
                                    "{{$itm->payroltype_name}}, <b>{{$itm->wrktype_name}}</b>",
                                    Ставка день: <b>{{$day_hr_rate}}</b>,
                                    ночь: <b>{{$night_hr_rate}}</b>
                                </div> </td>
                                <td class="text-right">{{$itm->wrkdays}} </td>
                                <td class="text-right">{{number_format($itm->day_wrkhrs,2)}}
                                <br><small>{{number_format($itm->day_hr_sum,2)}}</small></td>
                                <td class="text-right">{{number_format($itm->night_wrkhrs,2)}}
                                    <br><small>{{number_format($itm->night_hr_sum,2)}}</small></td>
                                <td class="text-right">{{number_format($itm->brkhrs,2)}}
                                    <br><small>{{number_format($itm->breaks_sum,2)}}</small></td>
                                <td class="text-right small">{{number_format($itm->brk_11_hrs,2)}}
                                    <br><small>{{number_format($itm->brk_11_sum,2)}}</small></td>
                                <td class="text-right small">{{number_format($itm->brk_21_hrs,2)}}
                                    <br><small>{{number_format($itm->brk_21_sum,2)}}</small></td>
                                <td class="text-right small">{{number_format($itm->brk_22_hrs,2)}}
                                    <br><small>{{number_format($itm->brk_22_sum,2)}}</small></td>
                                <td class="text-right">{{number_format($itm->day_hr_sum + $itm->night_hr_sum + $itm->breaks_sum,2)}}</td>
                            </tr>
                            <?php
                            $totDayWrkHrs += $itm->day_wrkhrs;
                            $totNightWrkHrs += $itm->night_wrkhrs;
                            $totBrkHrs += $itm->brkhrs;
                            $totBrk11Hrs += $itm->brk_11_hrs;
                            $totBrk21Hrs += $itm->brk_21_hrs;
                            $totBrk22Hrs += $itm->brk_22_hrs;
                            $totSum += $itm->day_hr_sum + $itm->night_hr_sum + $itm->breaks_sum;
                            ?>
                        @endforeach

                        <tr>
                            <td colspan="3" class="text-right">Итого:</td>
                            <td class="text-right font-weight-bold">{{number_format($totDayWrkHrs,2)}}</td>
                            <td class="text-right font-weight-bold">{{number_format($totNightWrkHrs,2)}}</td>
                            <td class="text-right font-weight-bold">{{number_format($totBrkHrs,2)}}</td>
                            <td class="text-right font-weight-bold small">{{number_format($totBrk11Hrs,2)}}</td>
                            <td class="text-right font-weight-bold small">{{number_format($totBrk21Hrs,2)}}</td>
                            <td class="text-right font-weight-bold small">{{number_format($totBrk22Hrs,2)}}</td>
                            <td class="text-right font-weight-bold">{{number_format($totSum,2)}}</td>
                        </tr>
                    </table>
                    {{-- ------------------------------------------------------------------------------------------}}

                </div>
            @endif
        @endif

    </div>

    {{--    <script src="{{ asset('js/rep43.js') }}" defer></script>--}}

@endsection
