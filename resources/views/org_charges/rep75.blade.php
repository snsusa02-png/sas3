@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 75;
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
        .rep-details td {
            padding: 1px;
            border-collapse: collapse;
            /*border: 1px solid gray;*/
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

                                    @if(1==0)
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
                                        <div class="form-group col-md-3 dpt_1" style="">
                                            <label for="s_month" class="required">Уч. период:</label>
                                            {!! Form::select('s_period', $data->for_periods??[], $search_params['s_period']??old('s_period'),
                                                            [
                                                            'id' => 's_period',
                                                            'class' => 'form-control',
                                                            'placeholder' => '-укажите-',
                                                            ])
                                                            !!}
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

                                    @if(1==1)
                                        <div class="form-group col-md-2">
                                            <label for="s_depname" class="">Подразделение:</label>
                                            {!! Form::select('s_depname', $data->depnames, $search_params['s_depname'],
                                                            [
                                                            'class' => 'form-control',
                                                            'placeholder' => '-все-',
                                                            ])
                                                            !!}
                                        </div>
                                    @endif

                                    @if(1==1)
                                        <div class="form-group col-md-2">
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
                                   href="{{ $retURL }}">
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
                           href="{{ route('reports.rep56',['s_period'=>$data->s_period, 'xls'=>1]) }}"
                           title="Выгрузить результаты в Excel">
                                        <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                                    </a>
                    @endif
                        <a class="btn btn-close btn-info btn-sm d-print-none"
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
                       class="table table-sm table-striped0 rep-data mt-3"
                       style="background-color: snow; font-size:13px; max-width:960px; align-self: center">
                    <thead>
                    <tr class="text-left small" style="vertical-align:middle;">
                        <td class="text-right small" style="width: 38px">№п/п</td>
                        <td class="text-center " style="width: 38px">ФИО</td>
                        <td class="text-center " style="width: 36px">Рабоч. дней</td>
                        <td class="text-center " style="width: 190px">Ставка, рабочие часы</td>
                        @foreach($data->cols as $col)
                            <?php
                            if (is_null($first_col_id))
                                $first_col_id = $col->id;

                            $cols_count++;
                            ?>
                            <td class="text-center" id="col_{{$col->id}}">{{$col->name}}</td>
                        @endforeach
                        <td class="text-center">ИТОГО</td>
                    </tr>
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
                        $tstyle = '';

                        // инициализация массива начислений сотрудника
                        foreach ($data->cols as $tcol) {
                            $line_sum[$tcol->id] = null;
                        }
                        $totOutSum = 0;

                        foreach ($rec->charges as $chrg) {
                            $totOutSum += ($chrg->dir * $chrg->charge_sum);
                            $totSum += ($chrg->dir * $chrg->charge_sum);
                            $line_sum[$chrg->chargetypeid] = $chrg->charge_sum;
                        }
                        ?>

                        <tr class="text-left {{$tr_class}}">
                            <td class="text-right small " style="font-size: 9px">
                                {{++$npp}}
                            </td>
                            <td class="text-left small" data-npp="{{$npp}}">
                                {{$rec->name}}
                                <br><div class="float-right small text-secondary">{{$rec->staffid}}</div>
                                @if(1==0)
                                    <a class="d-print-none "
                                       href="{{ route('stf_chrg_calcs.create', $rec->staffid)}}?returl={{Request::url()}}"
                                       title="Добавить запись">+</a>
                                @endif
                            </td>

                            <td class="text-center small">{{$rec->wrkdays}}</td>
                            <td style="padding: 1px;">
                                @if($rec->dw_cnt>0)

                                    <table class="tbl table-sm table-bordered rep-details mb-0"
                                           style="border: 1px solid black;font-size: 8pt;" width="100%">
                                        <tr class="small">
                                            <td rowspan="2" >Вид работ</td>
                                            <td colspan="3"  class="text-center">День</td>
                                            <td colspan="3"  class="text-center">Ночь</td>
                                            <td colspan="2"  class="text-center">Простой</td>
                                            <td colspan="2"  class="text-center">Ремонт</td>
                                            <td rowspan="2" class="text-center">Итого, &#8381;</td>
                                        </tr>
                                        <tr class="small text-center">
                                            <td style="width: 38px;">ставка, &#8381;/ч</td>
                                            <td style="width: 28px;">часы</td>
                                            <td style="width: 46px;">cумма</td>

                                            <td  style="width: 38px;">cтавка, &#8381;/ч</td>
                                            <td style="width: 28px;">часы</td>
                                            <td style="width: 46px;">сумма</td>

                                            <td style="width: 28px;">часы</td>
                                            <td style="width: 46px;">сумма</td>

                                            <td style="width: 28px;">часы</td>
                                            <td style="width: 64px;">сумма</td>
                                        </tr>
                                        @php($salary_sum = 0)
                                        @foreach($rec->wrkhrs as $itm)
                                            <tr class="small">
                                                <td  >
                                                    {{$itm->wrktypename??'-'}}
                                                </td>
                                                <td  class="text-center ">{{number_format($itm->day_hr_rate, 0)}}</td>
                                                <td  class="text-center ">{{number_format($itm->day_wrkhrs, 2)}}</td>
                                                <td  class="text-right font-weight-bold ">{!!number_format($itm->day_hr_rate*$itm->day_wrkhrs, 2, '.', '&nbsp;')!!}</td>

                                                <td  class="text-center ">{{number_format($itm->night_hr_rate, 0)}}</td>
                                                <td  class="text-center ">{{number_format($itm->night_wrkhrs, 2)}}</td>
                                                <td  class="text-right font-weight-bold ">{!! number_format($itm->night_hr_rate*$itm->night_wrkhrs, 2, '.', '&nbsp;')!!}</td>

                                                <td  class="text-center ">{{number_format($itm->b22_day_hrs + $itm->b22_night_hrs, 2)}}</td>
                                                <td  class="text-right font-weight-bold ">{!! number_format($itm->wait_sum, 2, '.', '&nbsp;')!!}</td>

                                                <td  class="text-center ">{{number_format($itm->b11_day_hrs + $itm->b11_night_hrs, 2)}}</td>
                                                <td  class="text-right font-weight-bold ">{!! number_format($itm->repair_sum, 2, '.', '&nbsp;')!!}</td>

                                                <td class="text-right font-weight-bold ">{!! number_format(
                                                    $itm->day_hr_rate*$itm->day_wrkhrs
                                                    + $itm->night_hr_rate*$itm->night_wrkhrs
                                                    + $itm->wait_sum
                                                    + $itm->repair_sum
                                                    , 2, '.', '&nbsp;')!!}
                                                </td>
                                            </tr>
                                            <?php
                                            $salary_sum += $itm->day_hr_rate * $itm->day_wrkhrs
                                                + $itm->night_hr_rate * $itm->night_wrkhrs
                                                + $itm->wait_sum
                                                + $itm->repair_sum;
                                            ?>
                                        @endforeach
                                        @if($rec->dw_cnt>1)
                                            <tr align="right" class="small text-right font-weight-bold ">
                                                <td colspan="11">Всего:</td>
                                                <td style="width: 64px;">{!! number_format($salary_sum, 2, '.', '&nbsp;')!!}</td>
                                            </tr>
                                        @endif
                                    </table>
                                @endif

                            </td>
                            <?php
                            // вывод начислений
                            foreach ($data->cols as $tcol) {
                                $sum = (is_null($line_sum[$tcol->id])) ? '' : number_format($line_sum[$tcol->id], 0, '.', '&nbsp;');
                                echo('<td class="text-right">' . $sum . '</td>');
                            }
                            echo('<td class="text-right font-weight-bold">' . number_format($totOutSum, 0, '.', '&nbsp;') . '</td>');
                            //                            echo('</tr>');
                            ?>
                        </tr>
                    @endforeach

                    @if(1==1)
                        <?php
                        $td_class = '';
                        $tdс_class = '';
                        ?>
                        <tr>
                            <td colspan="{{4+$cols_count}}" class="text-right" data-npp="{{$npp++}}">Всего:</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{!! number_format($totSum,0, '.', '&nbsp;')!!}</td>
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
