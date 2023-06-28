@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 51;
//$retURL = route('admin') . '#nsi-rep';
//$retURL = '/admin#nsi-rep';
$retURL = route('driver_works.index');

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

                                    <div class="form-group col-md-3">
                                        <label for="s_ownorgid" class="">Организация:</label>
                                        {!! Form::select('s_ownorgid', $data->ownorgs, $search_params['s_ownorgid'],
                                                        [
                                                        'class' => 'form-control',
                                                        'placeholder' => '-все-',
                                                        ])
                                                        !!}
                                    </div>

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
            @if ($recs->count()==0)

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
                        {{--                        @if(isset($s_begdate) and $s_begdate<>'')--}}
                        {{--                            с {{date_format(date_create($s_begdate),'d.m.Y')}}--}}
                        {{--                        @endif--}}
                        {{--                        @if(isset($s_enddate) and $s_enddate<>'')--}}
                        {{--                            по {{date_format(date_create($s_enddate),'d.m.Y')}}--}}
                        {{--                        @endif--}}

                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>

                    </div>

                    {{-- ------------------------------------------------------------------------------------------}}

                    <table class="table1 table-bordered table-sm table-data" border="0" style="background-color: white">
                        <tr>
                            <td rowspan="1">Работник</td>
                            <?php
                            $mindate = date_create($s_begdate);
                            $maxdate = date_create($s_enddate);
                            $date = clone $mindate;
                            //dd($date, $mindate, $maxdate);
                            $tdate = $date->format('Y-m-d');
                            $totSum = 0;
                            $cur_staffid = -1;

                            $pre_date = $base_pre_date;

                            $date = clone $mindate;
                            ?>
                            @while ($date <= $maxdate)
                                <td class="day">{{$date->format('d')}}</td>
                                <?php
                                $date->modify('+1 day');
                                ?>
                            @endwhile

                            <td rowspan="1" class="text-center">Сумма, &#8381;</td>
                        </tr>

                        @foreach($recs as $itm)

                            @if($itm->staffid<>$cur_staffid)

                                @if($cur_staffid<>-1)
                                    <?php
                                    //кол-во дней до конца месяца после последнего дня с данными о ЗП текущего работника
                                    $days_after = (strtotime($s_enddate) - strtotime($pre_date)) / 3600 / 24;
                                    //dd($days_after);
                                    ?>

                                    @for ($i = 0; $i < $days_after; $i++)
                                        <td class="cell"></td>
                                    @endfor

                                    <td class="text-right font-weight-bold">
                                        {{number_format($staffSum,2)}}
                                    </td>
                                    </tr>
                                @endif

                                <?php
                                $staff_name = $itm->staff_lname;
                                if (isset($itm->staff_fname)) {
                                    $staff_name .= ' ' . mb_substr($itm->staff_fname, 0, 1) . '.';
                                    if (isset($itm->staff_mname))
                                        $staff_name .= mb_substr($itm->staff_mname, 0, 1) . '.';
                                }

                                $cur_staffid = $itm->staffid;
                                $pre_date = $base_pre_date;
                                $staffSum = 0;
                                ?>
                                <tr>
                                    <td colspan=""
                                        class="text-center font-weight-bold font-italic text-nowrap" title="{{$itm->staffid}}">{{$staff_name}}</td>
                                    @endif


                                    <?php
                                    //дней перед началом работ
                                    $days_before = (strtotime($itm->wrkdate) - strtotime($pre_date)) / 3600 / 24 - 1;
                                    //dd($itm->wrkdate, $pre_date, $days_before);

                                    $cell_title = date_format(date_create($itm->wrkdate), 'd.m.Y')
                                        . ': ' . $itm->salary_sum . ' = '
                                        . number_format($itm->raid_sum, 2) . ' (рейс)';
                                    if (isset($itm->pdt_sum)) {
                                        $cell_title .= ' + ' . number_format($itm->pdt_sum, 2) . ' (простой)';
                                    }
                                    ?>
                                    @for ($i = 0; $i < $days_before; $i++)
                                        <td class="cell"></td>
                                    @endfor
{{--                                    <td class="cell work" title="{{$itm->wrkdate}}: {{$itm->salary_sum}} = {{$itm->raid_sum}}(рейс) + {{$itm->pdt_sum}}(простой)">--}}
                                    <td class="cell work" title="{{$cell_title}}">
{{--                                        <a href="{{route('driver_works.edit',$itm->id)}}" class="text-decoration-none" target="_blank">{{number_format($itm->salary_sum,0)}}</a>--}}
                                        {{number_format($itm->salary_sum,0)}}
                                    </td>
                                    <?php
                                    $staffSum += $itm->salary_sum;
                                    $totSum += $itm->salary_sum;
                                    $pre_date = $itm->wrkdate;
                                    ?>
                                    @endforeach

                                    @if($cur_staffid<>-1)
                                        <?php
                                        //кол-во дней до конца месяца после последнего дня с данными о ЗП текущего работника
                                        $days_after = (strtotime($s_enddate) - strtotime($pre_date)) / 3600 / 24;
                                        //dd($days_after);
                                        ?>

                                        @for ($i = 0; $i < $days_after; $i++)
                                            <td class="cell"></td>
                                        @endfor

                                        <td class="text-right font-weight-bold">
                                            {{number_format($staffSum,2)}}
                                        </td>
                                </tr>
                            @endif

                            <tr>
                                <td colspan="{{$month_days+1}}" class="text-right">Всего:</td>
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
