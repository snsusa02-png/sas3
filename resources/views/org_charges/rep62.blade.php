@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 62;
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
                <div id="_calc_selected_sum"
                     class="p-3 text-center bg-light  font-weight-bold w-25 border  border-danger rounded-pill"
                     style="position: sticky; top: 2em; display: none"></div>
                <div id="_calc_selected_qty"
                     class="p-3 text-center bg-light  font-weight-bold w-25 border  border-danger rounded-pill"
                     style="position: sticky; top: 2em; display: none"></div>

                <span class="float-right">
                    <a class="btn btn-warning btn-sm print-window d-print-none "
                       href="{{ route('stf_chrg_calcs.create', -1) }}"
                       title="Добавить запись">
                        <i class="fa fa-plus" aria-hidden="true"></i>
                    </a>
                    <a class="btn btn-warning btn-sm print-window d-print-none "
                       onclick="window.print();"
                       title="печать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>
                    @if(1==0)
                        <a class="btn btn-success btn-sm mr-3"
                           href="{{ route('reports.rep56',['date'=>$data->begdate]) }}?xls=1"
                           title="Выгрузить результаты в Excel">
                                        <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                                    </a>
                    @endif
                        <a class="btn btn-close btn-info btn-sm"
                           href="{{ $retURL }}">
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </a>
                        </span>

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

                <table id="results"
                       class="table table-sm table-striped rep-data mt-3"
                       style="background-color: snow; font-size:16px; max-width:960px; align-self: center">
                    <thead>
                    </thead>

                    <tbody>
                    <?php
                    $npp = 0;
                    $totSum = $totInpSum = $totOutSum = 0;
                    $cur_orgid = -1;
                    $cur_dep_name = '-1';
                    $cur_staffid = -1;

                    $line_sum = [];
                    ?>
                    @foreach($recs as $rec)
                        <?php

                        $tr_class = "";
                        $td_class = "";
                        $tdс_class = "";

                        //$tstyle = ($rec->inp_qty + $rec->out_qty > 0) ? 'background-color:#ffff94' : '';
                        $tstyle = '';
                        ?>

                        @if($rec->staffid <> $cur_staffid)

                            {{--Вывод по пред сотруднику--}}
                            @if( $cur_staffid <> -1 )
                                <tr>
                                    <td class="text-right font-weight-bold">Итого к выдаче:</td>
                                    <td class="text-right font-weight-bold">{{number_format($totOutSum, 0)}}</td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <Br>
                                    </td>
                                </tr>
                            @endif

                            @if(1==0 and $rec->orgid <> $cur_orgid)
                                <tr class="text-left">
                                    <td colspan={{3}}>{{$rec->org_name}}</td>
                                </tr>
                                <?php
                                $cur_orgid = $rec->orgid;
                                $cur_dep_name = '-1';
                                ?>
                            @endif

                            @if(1==0 and $rec->dep_name <> $cur_dep_name)
                                <tr class="text-left">
                                    <td colspan="{{3}}" class="small" style="background-color: #ecf6f9">
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        Подразделение:
                                        <b>{{(trim($rec->dep_name)=='')?'-не указано-':$rec->dep_name}}</b></td>
                                </tr>
                                @php($cur_dep_name = $rec->dep_name)
                            @endif
                            <?php
                            $cur_staffid = $rec->staffid;
                            $pre_chargetypeid = $first_col_id;
                            //echo('<hr>');var_dump('$pre_chargetypeid =', $pre_chargetypeid);
                            ?>
                            <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                                <td colspan="2" class="text-left" data-npp="{{$npp}}">
{{--                                    <span class="small">{{++$npp}}.</span>--}}
                                    <b>{{$rec->lname}} {{$rec->fname}} {{$rec->mname}}</b>,
                                    <div class="small"> должность: {{$rec->postname}},
                                        подразделение: {{$rec->dep_name??'-не указано-'}},
                                        {{$rec->org_name}}
                                    </div>
                                </td>
                            <?php
                            $totOutSum = 0;
                            ?>
                        @endif

                        <tr>
                            <td class="text-left ">
                                ({{$rec->dir==1?'+':'-'}})
                                {{$rec->chargetype_name}}
                                <span class="small float-right">
{{--                                    ({{date_format(date_create($rec->docdate),'d.m.Y')}} {{isset($rec->notes)?', '.$rec->notes:''}})--}}
                                    {{$rec->notes}}
                                </span>
                            </td>
                            <td class="text-right ">{{number_format($rec->charge_sum, 0)}}</td>
                        </tr>

                        <?php
                        $totOutSum += ($rec->dir * $rec->charge_sum);
                        $totSum += ($rec->dir * $rec->charge_sum);
                        ?>
                    @endforeach

                    {{-- последняя запись --}}
                    @if( $cur_staffid <> -1 )
                        <tr>
                            <td class="text-right font-weight-bold">Итого к выдаче:</td>
                            <td class="text-right font-weight-bold">{{number_format($totOutSum, 0)}}</td>
                        </tr>
                    @endif

                    @if(1==0)
                        <?php
                        $td_class = '';
                        $tdс_class = '';
                        ?>
                        <tr>
                            <td colspan="1" class="text-right" data-npp="{{$npp++}}">Всего:</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totSum,0)}}</td>
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
