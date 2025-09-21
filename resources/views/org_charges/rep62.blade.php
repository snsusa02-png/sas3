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
            border0: 1px solid #e2e2e2;
        }

        .rep-details0 table {
            border-collapse: collapse;
            border: 2px solid black;
        }

        .rep-details td {
            padding: 3px;
            border-collapse: collapse;
            border: 1px solid black;
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

            <div class="page p-2 container-fluid" style="font-family: 'Times New Roman'">

                {{--                <div class="mt-2 text-center"--}}
                {{--                     style="font-size: 18px;">--}}
                {{--                    <h4>{{$thisTitle}}</h4>--}}
                {{--                    <b>{{date_create($data->begdate)->format('d.m.Y')}}--}}
                {{--                        - {{date_create($data->enddate)->format('d.m.Y')}}</b>--}}
                {{--                    <span class="small"><br>по состоянию на {{now()}}</span>--}}
                {{--                    @if(1==0)--}}
                {{--                        <button class="btn btn-primary btn-sm d-print-none" type="button" data-toggle="collapse"--}}
                {{--                                data-target=".multi-collapse" aria-expanded="false"--}}
                {{--                                aria-controls="multiCollapseExample1 multiCollapseExample2">--}}
                {{--                            <i class="fa fa-eye-slash" aria-hidden="true"></i>--}}
                {{--                        </button>--}}
                {{--                    @endif--}}
                {{--                </div>--}}


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
                    @if($cur_staffid <> -1)
                        <div class="pagebreak"></div>
                    @endif

                    <table id="results"
                           class="table table-borderless rep-data mt-3" border="0"
                           style="background-color: snow; font-size:16px; max-width:960px; align-self: center">
                        <thead>
                        </thead>

                        <tbody>

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
                        <tr>
                            <td colspan="2" align="center" style="font-size: x-large">
                                <img src="/images/signs/gerb.jpg" style="width:150px;text-align: center">
                                <h3><b style="letter-spacing: 2px;">РАБОЧАЯ ВЕДОМОСТЬ</b></h3>
                            </td>
                        </tr>
                        <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                            <td colspan="1" class="text-left" data-npp="{{$npp}}">
                                <b>{{$rec->lname}} {{$rec->fname}} {{$rec->mname}}</b>
                                {{--                                <div class="small"> должность: <i>{{$rec->postname}}</i>,--}}
                                {{--                                    подразделение: <i>{{$rec->dep_name??'-не указано-'}},--}}
                                {{--                                        {{$rec->org_name}}</i>--}}
                                {{--                                </div>--}}
                            </td>
                            <td align="center">за период <br><b>{{date_create($data->begdate)->format('d.m.Y')}}
                                    - {{date_create($data->enddate)->format('d.m.Y')}}</b></td>
                        <?php
                        $totOutSum = 0;
                        ?>

                        @if (count($rec->drvrhrs) > 0)
                            <tr>
                                <td class="text-left " colspan="2">
                                    <table class="tbl table-sm table-bordered rep-details mb-2"
                                           style="border: 2px solid black;" width="100%">
                                        <tr class="small">
                                            <td rowspan="2">Вид работ</td>
                                            <td colspan="3" class="text-center">День</td>
                                            <td colspan="3" class="text-center">Ночь</td>
                                            <td colspan="2" class="text-center">Простой</td>
                                            <td colspan="2" class="text-center">Ремонт</td>
                                            <td colspan="3" class="text-center">Итого</td>
                                        </tr>
                                        <tr class="small text-center">
                                            <td>Ставка, &#8381;/ч</td>
                                            <td>Часов</td>
                                            <td>Сумма</td>

                                            <td>Ставка, &#8381;/ч</td>
                                            <td>Часов</td>
                                            <td>Сумма</td>

                                            <td>Часов</td>
                                            <td>Сумма</td>

                                            <td>Часов</td>
                                            <td>Сумма</td>

                                            <td>Часов</td>
                                            <td>Дней</td>
                                            <td>Сумма</td>

                                        </tr>
                                        @foreach($rec->drvrhrs as $itm)
                                            <tr class="small">
                                                <td>
                                                    {{$itm->wrktypename}}
                                                </td>
                                                <td class="text-center ">{{number_format($itm->day_hr_rate, 0)}}</td>
                                                <td class="text-center ">{{number_format($itm->day_wrkhrs, 2)}}</td>
                                                <td class="text-right font-weight-bold ">{!!number_format($itm->day_hr_rate*$itm->day_wrkhrs, 2, '.', '&nbsp;')!!}</td>

                                                <td class="text-center ">{{number_format($itm->night_hr_rate, 0)}}</td>
                                                <td class="text-center ">{{number_format($itm->night_wrkhrs, 2)}}</td>
                                                <td class="text-right font-weight-bold ">{!! number_format($itm->night_hr_rate*$itm->night_wrkhrs, 2, '.', '&nbsp;')!!}</td>

                                                <td class="text-center ">{{number_format($itm->wait_hrs, 2)}}</td>
                                                <td class="text-right font-weight-bold ">{!! number_format($itm->wait_sum, 2, '.', '&nbsp;')!!}</td>

                                                <td class="text-center ">{{number_format($itm->repair_hrs, 2)}}</td>
                                                <td class="text-right font-weight-bold ">{!! number_format($itm->repair_sum, 2, '.', '&nbsp;')!!}</td>

                                                <td class="text-center ">{{number_format(
                                                    $itm->day_wrkhrs
                                                    + $itm->night_wrkhrs
                                                    + $itm->wait_hrs
                                                    + $itm->repair_hrs
                                                    , 2)}}</td>
                                                <td class="text-center">
                                                    {{$itm->wrkdays_cnt}}
                                                </td>
                                                <td class="text-right font-weight-bold ">{!! number_format(
                                                    $itm->day_hr_rate*$itm->day_wrkhrs
                                                    +$itm->night_hr_rate*$itm->night_wrkhrs
                                                    +$itm->wait_sum
                                                    +$itm->repair_sum, 2, '.', '&nbsp;')!!}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        @endif

                        @if (count($rec->wrkhrs) > 0)
                            <tr>
                                <td class="text-left " colspan="2">
                                    <table class="tbl table-sm table-bordered mb-2" width="100%">
                                        <tr class="small">
                                            <th rowspan="2">Вид работ</th>
                                            <th colspan="3" class="text-center">День</th>
                                            <th colspan="3" class="text-center">Ночь</th>
                                            <th rowspan="2" class="text-center">Итого, &#8381;</th>
                                        </tr>
                                        <tr class="small">
                                            <td>Ставка, &#8381;</td>
                                            <td>Часов</td>
                                            <td>Сумма, &#8381;</td>

                                            <td>Ставка, &#8381;</td>
                                            <td>Часов</td>
                                            <td>Сумма, &#8381;</td>
                                        </tr>
                                        @foreach($rec->wrkhrs as $itm)
                                            <tr class="small">
                                                <td>
                                                    {{$itm->wrktypename??'-разное-'}}
                                                </td>
                                                <td class="text-right ">{{number_format($itm->day_hr_cost, 0)}}</td>
                                                <td class="text-right ">{{number_format($itm->day_tot_hrs, 2)}}</td>
                                                <td class="text-right font-weight-bold ">{{number_format($itm->day_hr_cost*$itm->day_tot_hrs, 2)}}</td>

                                                <td class="text-right ">{{number_format($itm->night_hr_cost, 0)}}</td>
                                                <td class="text-right ">{{number_format($itm->night_tot_hrs, 2)}}</td>
                                                <td class="text-right font-weight-bold ">{{number_format($itm->night_hr_cost*$itm->night_tot_hrs, 2)}}</td>
                                                <td class="text-right font-weight-bold ">{{number_format($itm->day_hr_cost*$itm->day_tot_hrs + $itm->night_hr_cost*$itm->night_tot_hrs, 2)}}</td>

                                            </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td class="text-right" style="text-align: center;" colspan="2">
                                <table class="tbl table-bordered text-center rep-details mb-2"
                                       style="border: 2px solid black;" width="100%">
                                    <tr>
                                        <td style="font-size: xx-large">ЗАРПЛАТА:</td>
                                        <td style="font-size: xx-large">{!! number_format($rec->salary_sum,2, '.', '&nbsp;')!!}
                                            руб
                                        </td>
                                        <td class="small" style="width: 8cm;"><br>
                                            <hr size="1" style="margin-bottom:0rem;">
                                            <sup style="font-size: 0.6em">(ФИО и подпись)</sup></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right" style="text-align: center;" colspan="2">
                                @php($totOutSum=0)
                                <table class="tbl text-center rep-details mb-2" width="100%"
                                       style="border: 2px solid black;">
                                    <tr>
                                        <td></td>
                                        <td class="text-center">Комментарий:</td>
                                        <td class="text-center">Сумма:</td>
                                        <td class="text-center" style="width: 8cm;">Подпись:</td>
                                    </tr>
                                    @foreach($rec->charges as $chrg)
                                        <tr>
                                            <td>
                                                {{$chrg->chargetype_name}}
                                            </td>
                                            <td class="small">
                                                {{$chrg->notes}}
                                            </td>
                                            <td class="text-right small">{!! number_format($chrg->dir*$chrg->charge_sum, 2, '.', '&nbsp;')!!}</td>
                                            <td class="small"></td>
                                        </tr>
                                        <?php
                                        $totOutSum += ($chrg->dir * $chrg->charge_sum);
                                        //                                        $totSum += ($chrg->dir * $chrg->charge_sum);
                                        ?>
                                    @endforeach
                                    {{--                                    <tr>--}}
                                    {{--                                        <td class="text-right font-weight-bold" style="font-size: 1.2rem">Итого к выдаче:</td>--}}
                                    {{--                                        <td class="text-right font-weight-bold" style="font-size: 1.2rem">{{number_format($totOutSum, 2)}}</td>--}}
                                    {{--                                        <td class="small" style="width: 130pt;"><br>--}}
                                    {{--                                            <hr size="1" style="margin-bottom:0rem;">--}}
                                    {{--                                            <sup style="font-size: 0.6em">(ФИО и подпись)</sup></td>--}}
                                    {{--                                    </tr>--}}
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right" style="text-align: center;" colspan="2">
                                <table class="tbl table-bordered text-center rep-details mb-2"
                                       style="border: 2px solid black;" width="100%">
                                    <tr>
                                        <td style="font-size: xx-large">ИТОГО К ВЫДАЧЕ:</td>
                                        <td style="font-size: xx-large">{!!number_format($totOutSum,2, '.', '&nbsp;')!!}
                                            руб
                                        </td>
                                        <td class="small" style="width:8cm;"><br>
                                            <hr size="1" style="margin-bottom:0rem;">
                                            <sup style="font-size: 0.6em">(ФИО и подпись)</sup></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        </tbody>
                        <tfoot>
                    </table>
                @endforeach

                {{-- последняя запись --}}


            </div>
        @endif

    </div>

    <script src="{{ asset('js/rep53.js') }}" defer></script>

@endsection
