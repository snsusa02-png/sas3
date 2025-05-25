@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 73;
$retURL = $data->returl ?? route('paydocs.index');

$report = \App\report::find($thisObjId);

if (!isset($report))
    return redirect($retURL);

$thisTitle = $report->title ?? $report->name;
$action_url = route('reports.rep' . $thisObjId);

$userid = \Auth::user()->id;
$usrrights = [];
$usrrights['link_tasks'] = false; //\App\usrsysright::isUserHasRightByCode_cached($userid, 'tasks.create');

$cols_count = 0;
$first_col_id = null;

?>
@section('title')
    {{$thisTitle}}
@endsection

@section('content')
    <style>
        @media print {
            @page {
                size: A4 portrait;
                /*size: portrait;*/
                /*size:auto;   !* auto is the initial value *!*/
                /* this affects the margin in the printer settings */
                margin: 5mm 5mm 5mm 5mm;
            }

            .pagebreak {
                page-break-inside: avoid;
                page-break-before: always;
            }
        }

        .rep-data td {
            padding: 5px;
            border-collapse: collapse;
            border: 1px solid #e2e2e2;
        }

        .page {
            background-color: white;
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
                                    @if(1==1)
                                        <div class="form-group col-md-2 dpt_1" style="">
                                            <label for="s_month" class="required">Год-Месяц:</label>
                                            {!! Form::select('s_ym', $data->yms??[], $search_params['s_ym']??old('s_ym'),
                                                            [
                                                            'id' => 's_ym',
                                                            'class' => 'form-control',
                                                            'placeholder' => '-укажите-',
                                                            ])
                                                            !!}
                                        </div>
                                    @else
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
                                    @endif

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
                                    @if(1==0)
                                        <div class="form-group col-md-3">
                                            <label for="s_ownorgid" class="">Сотрудник:</label>
                                            {!! Form::text('s_stf_name', $search_params['s_stf_name'],
                                                            [
                                                            'class' => 'form-control',
                                                            'placeholder' => '-ФИО-',
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

            <div class="page p-2 container-fluid">

                <div class="mt-2 text-center"
                     style="font-size: 18px;">
                    <h4>{{$thisTitle}}</h4>
                    <b>{{date_create($data->begdate)->format('d.m.Y')}}
                        - {{date_create($data->enddate)->format('d.m.Y')}}</b>
                    <span class="small"><br>по состоянию на {{now()}}</span>
                    @if(1==0)
                        <button class="btn btn-primary btn-sm d-print-none" type="button" data-toggle="collapse"
                                data-target=".multi-collapse" aria-expanded="false"
                                aria-controls="multiCollapseExample1 multiCollapseExample2">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>


                <?php
                $npp = 0;
                $totPrizeSum = $totPrizeHrs = 0;
                $cur_orgid = -1;
                $cur_dep_name = '-1';
                $cur_staffid = -1;

                $line_sum = [];
                ?>
                <table class="table table-sm table-striped rep-data mt-3"
                       style="background-color: snow; font-size:16px; width:960px"
                       align=center>
                    <thead>
                    <tr class="text-left small" valign="top">
                        <td class="text-left">Водитель</td>
                        <td class="text-left">Период работ, кол-во смен</td>
                        <td class="text-left">Отработано всего, ч</td>
                        <td class="text-left">Отработано свыше 340 ч, ч</td>
                        <td class="text-left">Ставка, &#8381;/ч</td>
                        <td class="text-right">Премия, &#8381;</td>
                    </tr>

                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $saleSum = $buySum = $totSum = 0;
                    ?>
                    @foreach($recs as $rec)
                        <?php
                        $line_sum = $rec->prize_sum;
                        $td_class = ($line_sum < 0) ? 'text-danger' : (($line_sum > 0) ? 'text-success' : '');
                        ?>

                        <tr class="text-left">
                            <td class="text-left small">{{$rec->lname}} {{$rec->fname}} {{$rec->mname}}
                                <div class="small float-right">{{$rec->orgname}}<br>{{$rec->postname}}</div> </td>
                            <td class="text-center small">{{date_format(date_create($rec->min_wrkdate), 'd')}} - {{date_format(date_create($rec->max_wrkdate), 'd')}}, {{$rec->cnt}}</td>
                            <td class="text-right small">{{number_format($rec->wrkhrs,2)}}</td>
                            <td class="text-right small">{{number_format($rec->prize_hrs,2)}}</td>
                            <td class="text-right small">{{number_format($rec->hr_rate,2)}}</td>
                            <td class="text-right small {{$td_class}}">{{number_format($line_sum,2)}}
                        </tr>
                        <?php
                        $totPrizeSum += $rec->prize_sum;
                        $totPrizeHrs += $rec->prize_hrs;
                        ?>
                    @endforeach

                    @if(1==1)

                        {{--                            <tr class="text-left" style="background-color: #dacf64">--}}
                        {{--                                <td colspan="13" class="text-left pl-2"></td>--}}
                        {{--                            </tr>--}}
                        <tr>
                            <td colspan="3" class="text-right">Всего:</td>
                            <td class="text-right font-weight-bold">{{number_format($totPrizeHrs,2)}}</td>
                            <td></td>
                            <td class="text-right font-weight-bold">{{number_format($totPrizeSum,2)}}</td>
                        </tr>
                    @endif
                    </tbody>
                    <tfoot>
                </table>


            </div>
        @endif

    </div>

    <script src="{{ asset('js/rep53.js') }}" defer></script>

@endsection
