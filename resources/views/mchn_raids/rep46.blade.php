@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 46;
//$retURL = route('admin') . '#nsi-rep';
//$retURL = '/admin#nsi-rep';
$retURL = route('mchn_raids.index');

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
        .rep-data td {
            padding: 5px;
            border-collapse: collapse;
            border: 1px solid #e2e2e2;
        }

        .page {
            background-color: white;
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
                                        <label for="s_period_type" class="">Тип периода:</label>
                                        {!! Form::select('s_period_type', $data->period_types, $search_params['s_period_type'],
                                                        [
                                                        'id' => 's_period_type',
                                                        'class' => 'form-control small',
                                                        'placeholder' => '-укажите-',
                                                        ])
                                                        !!}
                                    </div>

                                    <div class="form-group col-md-2 dpt_1 dpt_9 " style="display: none">
                                        <label for="s_begdate" class="required">Начало периода:</label>
                                        <input type="date" class="form-control text-center"
                                               name="s_begdate" id="s_begdate"
                                               value="{{$search_params['s_begdate']??''}}"
                                        />
                                    </div>

                                    <div class="form-group col-md-2 dpt_9 " style="display: none">
                                        <label for="s_enddate">Окончание периода:</label>
                                        <input type="date" class="form-control text-center"
                                               name="s_enddate" id="s_enddate"
                                               value="{{$search_params['s_enddate']??''}}"
                                        />
                                    </div>

                                    <div class="form-group col-md-2 dpt_2" style="display: none">
                                        <label for="s_month" class="required">Месяц:</label>
                                        {!! Form::select('s_month', $data->monthes??[], $search_params['s_month'],
                                                        [
                                                        'id' => 's_month',
                                                        'class' => 'form-control',
                                                        'placeholder' => '-укажите-',
                                                        ])
                                                        !!}
                                    </div>

                                    <div class="form-group col-md-2 dpt_3 " style="display: none">
                                        <label for="s_quarter" class="required">Квартал:</label>
                                        {!! Form::select('s_quarter', $data->quarters??[], $search_params['s_quarter'],
                                                        [
                                                        'id' => 's_quarter',
                                                        'class' => 'form-control',
                                                        'placeholder' => '-укажите-',
                                                        ])
                                                        !!}
                                    </div>

                                    <div class="form-group col-md-2 dpt_4 " style="display: none">
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
                                        <label for="s_ownorgid" class="">Владелец:</label>
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
                                    <div class="form-group col-md-3">
                                        <label for="name">Получатель:</label>
                                        {!! Form::select('s_orgid', $data->orgs, $search_params['s_orgid'],
                                                        [
                                                        'class' => 'form-control',
                                                        'placeholder' => '-все-',
                                                        ])
                                                        !!}
                                    </div>

                                    @if(1==0)
                                        <div class="form-group col-md-3">
                                            <label for="s_categoryid">Категория:</label>
                                            {!! Form::select('s_categoryid', $data->categories, $search_params['s_categoryid']??'',
                                                            [
                                                            'class' => 'form-control',
                                                            'placeholder' => '-все-',
                                                            ])
                                                            !!}
                                        </div>
                                    @endif

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

                $routes = [
                    915 => 'invoices.edit'
                ];
                ?>

                <div class="page p-2 container-fluid">

                    <span class="float-right">
                    <a class="btn btn-warning btn-sm print-window d-print-none "
                       onclick="window.print();"
                       title="печать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>
                        @if(1==1)
                            <a class="btn btn-success btn-sm mr-3"
                               href="{{ route('reports.rep46')  }}?xls=1" title="Выгрузить результаты в Excel">
                                        <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                                    </a>
                        @endif
                        <a class="btn btn-close btn-info btn-sm"
                           href="{{ $retURL  }}">
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </a>
                        </span>

                    <div class="font-weight-bold mt-2" align="center"
                         style="font-size: 18px;">
                        <h4>{{$thisTitle}}</h4>
                        {{$data->period_title}}
                        <span class="small"><br>{{$data->period_subtitle}}</span>
                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>

                        @if(1==0)
                            <button class="btn btn-primary btn-sm d-print-none" type="button" data-toggle="collapse"
                                    data-target=".multi-collapse" aria-expanded="false"
                                    aria-controls="multiCollapseExample1 multiCollapseExample2">
                                <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>

                    <br>
                    <h4 class="text-center">Оборот по работе</h4>
                    <table class="table table-sm table-striped rep-data mt-3"
                           style="background-color: snow; font-size:16px; width:960px"
                           align=center>
                        <thead>
                        <tr class="text-left small" valign="top">
                            {{--                            <td class="text-center">Дата</td>--}}
                            <td class="text-left">Компания/физ. лицо, Место выгрузки</td>
                            <td class="text-center">Диспетчер</td>
                            <td class="text-center">Кол-во рейсов</td>
                            <td class="text-center">Товар/Услуга</td>
                            <td class="text-center">ЕИ</td>
                            <td class="text-right">Объем отгрузки</td>
                            <td class="text-right">Цена за ЕИ</td>
                            <td class="text-right">Сумма, &#8381;</td>
                            <td class="text-right">Сальдо, &#8381;</td>
                        </tr>

                        </thead>
                        <tbody>
                        <?php
                        $npp = 0;
                        $curOwnOrgID = -1;
                        $curOwnOrgName = '';
                        $curRqstOrgID = -1;
                        $curPayDate = -1;
                        $totUnloadSum = $totRaidQty = $daySum = $dayRaidQty = 0;
                        ?>
                        @foreach($recs as $rec)

                            <?php
                            $wrkdate = date_create($rec->wrkdate)->format('d.m.Y');
                            ?>
                            @if(1==0 and $wrkdate<>$curPayDate)
                                @if($curPayDate <>-1 )
                                    <tr data-toggle="collapse" data-target=".date_{{$tr_date}}" style="cursor: pointer">
                                        <td colspan="2" class="text-right small">Итого за {{$curPayDate}}:</td>
                                        <td class="text-right font-weight-bold h6">{{number_format($dayRaidQty,0)}}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-right font-weight-bold h6">{{number_format($daySum,2)}}</td>
                                        <td></td>
                                    </tr>
                                @endif
                                <?php
                                $tr_date = date_create($rec->wrkdate)->format('dmY');
                                ?>
                                <tr class="text-left" style="background-color: #edffe0">
                                    <td colspan="9" class="text-left pl-2 h6">
                                        @if(1==0)
                                            <span data-toggle="collapse" data-target=".date_{{$tr_date}}"
                                                  class="btn btn-light btn-sm "><b>{{$wrkdate}}</b>
                                            <i class="fa fa-eye-slash d-print-none" aria-hidden="true"></i>
                                        </span>
                                        @else
                                            <b>{{$wrkdate}}</b>
                                        @endif
                                    </td>
                                </tr>
                                <?php
                                $curPayDate = $wrkdate;
                                $daySum = $dayRaidQty = 0;
                                ?>
                            @endif
                            @if( $rec->suporgid <> $curOwnOrgID)
                                <tr>
                                    <td colspan="9" class="font-weight-bold font-italic">{{$rec->ownorgname}}</td>
                                </tr>
                                <?php
                                $curOwnOrgID = $rec->suporgid;
                                ?>
                            @endif
                            <?php
                            $td_class = ($rec->org_saldo < 0) ? 'text-danger' : (($rec->org_saldo > 0) ? 'text-success' : '');
                            ?>

                            {{--                            <tr class="text-left collapse show date_{{$tr_date}} multi-collapse">--}}
                            <tr class="text-left collapse show date_{{$tr_date??''}} multi-collapse">
                                <td class="text-left small">
                                    <a href="{{route('orgs.edit',$rec->orgid)}}" target="_blank"
                                       class="text-decoration-none">{{$rec->orgname}}</a>
                                    <div class="float-right">{{$rec->unload_placename}}</div>
                                    <div
                                        class="text-center mt-1 small">{{date_create($rec->min_wrkdate)->format('d.m.Y')}}
                                        .. {{date_create($rec->max_wrkdate)->format('d.m.Y')}}</div>
                                    <div class="text-right">{{$rec->ownorgname}}</div>
                                </td>
                                <td class="text-left small">{{$rec->dispuser_name}}</td>
                                <td class="text-right small">{{$rec->raid_qty}}</td>
                                <td class="text-left small">{{$rec->refitm_name}}</td>
                                <td class="text-center small"> {{$rec->unit}}</td>
                                <td class="text-right small">{{number_format($rec->unload_qty,2)}}
                                <td class="text-right small">
                                    @if($rec->unload_qty>0)
                                        {{--                                        {{number_format($rec->unload_sum/$rec->unload_qty,2)}}--}}
                                        {{number_format($rec->itm_price,2)}}
                                    @else
                                        0
                                    @endif
                                </td>
                                <td class="text-right">{{number_format($rec->unload_sum,2)}}
                                <td class="text-right {{$td_class}}">{{number_format($rec->org_saldo,2)}}

                            </tr>
                            <?php
                            $totUnloadSum += $rec->unload_sum;
                            $daySum += $rec->unload_sum;

                            $dayRaidQty += $rec->raid_qty;
                            $totRaidQty += $rec->raid_qty;
                            ?>
                        @endforeach

                        @if(1==1)
                            @if($curPayDate <>-1 )
                                <tr data-toggle="collapse" data-target=".date_{{$tr_date}}" style="cursor: pointer">
                                    <td colspan="2" class="text-right small">Итого за {{$curPayDate}}:</td>
                                    <td class="text-right font-weight-bold h6">{{number_format($dayRaidQty,0)}}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right font-weight-bold h6">{{number_format($daySum,2)}}</td>
                                    <td></td>
                                </tr>
                            @endif

                            <tr class="text-left" style="background-color: #dacf64">
                                <td colspan="9" class="text-left pl-2"></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-right">Всего:</td>
                                <td class="text-right font-weight-bold h6">{{number_format($totRaidQty,0)}}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="text-right font-weight-bold">{{number_format($totUnloadSum,2)}}</td>
                                <td></td>
                            </tr>
                        @endif
                        </tbody>
                        <tfoot>
                    </table>

                    <br>
                    <h4 class="text-center">Карьеры</h4>
                    <table class="table table-sm table-striped rep-data mt-3"
                           style="background-color: snow; font-size:16px; width:960px"
                           align=center>
                        <thead>
                        <tr class="text-left small" valign="top">
                            <td class="text-left">Название</td>
                            <td class="text-center">Товар/Услуга</td>
                            <td class="text-center">ЕИ</td>
                            <td class="text-right">Объем, ЕИ</td>
                            <td class="text-right">Цена за ЕИ, &#8381;</td>
                            <td class="text-right">Сумма, &#8381;</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $npp = 0;
                        $totLoadSum = 0;
                        ?>
                        @foreach($recs2 as $rec)

                            <tr class="text-left">
                                <td class="text-left "><span class="small"> {{$rec->load_placename}}</span>
                                    {{--                                    <span class="small ml-2">{{$rec->load_place_address??''}}</span>--}}
                                    <div>{{$rec->suporg_name}}</div>
                                    <div class="text-right small">{{$rec->ownorg_name}}</div>
                                </td>
                                <td class="text-left small">{{$rec->refitm_name}}</td>
                                <td class="text-center small">{{$rec->unit}}</td>
                                <td class="text-right small">{{number_format($rec->load_qty,2)}}
                                <td class="text-right small">{{number_format($rec->load_price,2)}}
                                <td class="text-right">{{number_format($rec->load_qty*$rec->load_price,2)}}
                            </tr>
                            <?php
                            $totLoadSum += $rec->load_qty * $rec->load_price;
                            ?>
                        @endforeach

                        @if(1==1)
                            <tr class="text-left" style="background-color: #dacf64">
                                <td colspan="7" class="text-left pl-2"></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-right">Всего:</td>
                                <td class="text-right font-weight-bold">{{number_format($totLoadSum,2)}}</td>
                            </tr>
                            <tr class="text-left" style="background-color: #b1da64">
                                <td colspan="7" class="text-left pl-2"></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-right">Баланс, &#8381;:</td>
                                <td class="text-right font-weight-bold">{{number_format($totUnloadSum-$totLoadSum,2)}}</td>
                            </tr>
                        @endif
                        </tbody>
                        <tfoot>
                    </table>

                    <br>
                    <h4 class="text-center">Водители</h4>
                    <table class="table table-sm table-striped rep-data mt-3"
                           style="background-color: snow; font-size:16px; width:960px"
                           align=center>
                        <thead>
                        <tr class="text-left small" valign="top">
                            <td rowspan="2" class="text-left">ФИО, Техника</td>
                            <td rowspan="2" class="text-right">Кол-во рейсов</td>
                            <td colspan="2" class="text-center">ЗП</td>
                            <td colspan="2" class="text-center">Оплач. простой</td>
                            <td rowspan="2" class="text-center">Всего, &#8381;</td>
                        </tr>
                        <tr>
                            <td class="text-center small">Время, ч</td>
                            <td class="text-center small">Сумма, &#8381;</td>

                            <td class="text-center small">Время, ч</td>
                            <td class="text-center small">Сумма, &#8381;</td>

                        </tr>

                        </thead>
                        <tbody>
                        <?php
                        $npp = 0;
                        $totRaidQty = 0;
                        $totSalarySum = 0;
                        $totWrkHrs = $totBrkHrs = 0;
                        $totWrkSum = $totBrkSum = 0;
                        $totSum = 0;
                        ?>
                        @foreach($recs3 as $rec)

                            <tr class="text-left">
                                <td class="text-left ">{{$rec->driver_name}}
                                    <div class="float-right small text-secondary">{{$rec->machine_name}}</div>
                                </td>

                                <td class="text-right ">{{number_format($rec->raid_qty,0)}}
                                <td class="text-right small">{{number_format($rec->wrkhrs,1)}}
                                <td class="text-right ">{{number_format($rec->hr_sum,2)}}

                                <td class="text-right small text-secondary">{{number_format($rec->brkhrs,1)}}
                                <td class="text-right ">{{number_format($rec->breaks_sum,2)}}

                                <td class="text-right ">{{number_format($rec->hr_sum+$rec->breaks_sum,2)}}
                            </tr>
                            <?php
                            $totRaidQty += $rec->raid_qty;
                            $totSalarySum += $rec->hr_sum;

                            $totWrkHrs += $rec->wrkhrs;
                            $totWrkSum += $rec->hr_sum;

                            $totBrkHrs += $rec->brkhrs;
                            $totBrkSum += $rec->breaks_sum;

                            $totSum += $rec->hr_sum + $rec->breaks_sum;
                            ?>
                        @endforeach

                        @if(1==1)
                            <tr class="text-left" style="background-color: #dacf64">
                                <td colspan="9" class="text-left pl-2"></td>
                            </tr>
                            <tr>
                                <td colspan="1" class="text-right">Всего:</td>
                                <td class="text-right font-weight-bold small">{{number_format($totRaidQty,0)}}</td>

                                <td class="text-right font-weight-bold small">{{number_format($totWrkHrs,1)}}</td>
                                <td class="text-right font-weight-bold">{{number_format($totWrkSum,2)}}</td>

                                <td class="text-right font-weight-bold small">{{number_format($totBrkHrs,1)}}</td>
                                <td class="text-right font-weight-bold">{{number_format($totBrkSum,2)}}</td>

                                <td class="text-right font-weight-bold">{{number_format($totSum,2)}}</td>
                            </tr>
                            <tr class="text-left" style="background-color: #b1da64">
                                <td colspan="9" class="text-left pl-2"></td>
                            </tr>
                            <tr>
                                <td colspan="6" class="text-right">
                                    Баланс, &#8381;:
                                </td>
                                <td class="text-right font-weight-bold">{{number_format($totUnloadSum-$totLoadSum-$totSum,2)}}</td>
                            </tr>
                        @endif
                        </tbody>
                        <tfoot>
                    </table>

                    @if(1==0)
                        {{-- 2024-02-11 Отчет по отгрузкам и поступлениям на склад --}}
                        <br>
                        <h4 class="text-center">Отгрузки и поступления на склад</h4>
                        {{--                    <table id="results4"--}}
                        {{--                           class="table table-sm table-striped rep-data mt-3"--}}
                        {{--                           style="background-color: snow; font-size:16px; max-width:960px; align-self: center">--}}
                        <table class="table table-sm table-striped rep-data mt-3"
                               style="background-color: snow; font-size:16px; width:960px"
                               align=center>
                            <thead>
                            <tr class="text-left small" style="vertical-align:middle;">
                                <td class="text-right small" style="width: 38px">№п/п</td>
                                <td class="text-center">Наименование продукции</td>
                                <td class="text-center">ЕИ</td>
                                <td class="text-right">Вх. остаток, ЕИ</td>
                                <td class="text-right">Приход, ЕИ</td>
                                <td class="text-right">Расход, ЕИ</td>
                                <td class="text-right">Приход, &#8381;</td>
                                <td class="text-right">Отгрузка, &#8381;</td>
                                <td class="text-right">Исх. остаток, ЕИ</td>
                            </tr>
                            </thead>

                            <tbody>
                            <?php
                            $npp = 0;
                            $totSum = $totInpSum = $totOutSum = 0;
                            ?>
                            @foreach($recs4 as $rec)
                                <?php

                                $tr_class = "";
                                $td_class = "";
                                $tdс_class = "";

                                $pre_qty = (isset($rec->pre_qty)) ? number_format($rec->pre_qty, 0) : '';
                                $inp_qty = (isset($rec->inp_qty)) ? number_format($rec->inp_qty, 0) : '';
                                $out_qty = (isset($rec->out_qty)) ? number_format($rec->out_qty, 0) : '';
                                $inp_sum = (isset($rec->inp_sum)) ? number_format($rec->inp_sum, 2) : '';
                                $sale_sum = (isset($rec->sale_sum)) ? number_format($rec->sale_sum, 2) : '';


                                $n_pre_qty = (isset($rec->pre_qty)) ? $rec->pre_qty : 0;
                                $n_inp_qty = (isset($rec->inp_qty)) ? $rec->inp_qty : 0;
                                $n_out_qty = (isset($rec->out_qty)) ? $rec->out_qty : 0;
                                $n_inp_sum = (isset($rec->inp_sum)) ? $rec->inp_sum : 0;
                                $n_sale_sum = (isset($rec->sale_sum)) ? $rec->sale_sum : 0;

                                $n_end_qty = $rec->pre_qty + $rec->inp_qty - $rec->out_qty;
                                $end_qty = number_format($n_end_qty, 0);

                                //                            if ($rec->sysobjid == 520)
                                if (1 == 1)
                                    $ref_url = null; //route('paydocs.edit', $rec->objid);
                                else
                                    $ref_url = null;

                                if ($n_inp_qty > 0)
                                    $inp_ref_url = route('reports.rep57_i') . '?ri_id=' . $rec->refitmid;
                                else
                                    $inp_ref_url = null;

                                if ($n_sale_sum > 0)
                                    $sale_ref_url = null; //route('paydocs.edit', $rec->objid);
                                else
                                    $sale_ref_url = null;


                                $tstyle = ($rec->inp_qty + $rec->out_qty > 0) ? 'background-color:#ffff94' : '';
                                ?>
                                <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                                    <td class="text-right small ">
                                        {{++$npp}}
                                    </td>
                                    <td class="text-left " data-npp="{{$npp}}">
                                        {{$rec->refitm_name}}
                                    </td>
                                    <td class="text-center small">
                                        {{$rec->refitm_unit}}
                                    </td>
                                    <td class="text-right small calced" data-num="{{$n_pre_qty}}">
                                        @if(isset($ref_url))
                                            <a href="{{$ref_url}}" target="_blank">{{$pre_qty}}</a>
                                        @else
                                            {{$pre_qty}}
                                        @endif
                                    </td>
                                    <td class="text-right small calced0" data-num0="{{0}}">
                                        @if(isset($inp_ref_url))
                                            <a href="{{$inp_ref_url}}" target="_blank">{{$inp_qty}}</a>
                                        @else
                                            {{$inp_qty}}
                                        @endif
                                    </td>
                                    <td class="text-right small calced" data-num="-{{$n_out_qty}}">
                                        @if(isset($ref_url))
                                            <a href="{{$ref_url}}" target="_blank">{{$out_qty}}</a>
                                        @else
                                            {{$out_qty}}
                                        @endif
                                    </td>
                                    <td class="text-right small calced" data-num="{{$n_inp_sum}}">
                                        @if(isset($inp_ref_url))
                                            <a href="{{$inp_ref_url}}" target="_blank">{{$inp_sum}}</a>
                                        @else
                                            {{$inp_sum}}
                                        @endif
                                    </td>
                                    <td class="text-right small calced" data-num="{{$n_sale_sum}}">
                                        @if(isset($sale_ref_url))
                                            <a href="{{$sale_ref_url}}" target="_blank">{{$sale_sum}}</a>
                                        @else
                                            {{$sale_sum}}
                                        @endif
                                    </td>
                                    <td class="text-right small calced" data-num="{{$n_end_qty}}">
                                        {{$end_qty}}
                                    </td>
                                </tr>
                                <?php
                                $totInpSum += $rec->inp_sum;
                                $totOutSum += $rec->sale_sum;
                                ?>
                            @endforeach
                            @if(1==1)
                                <?php
                                $td_class = '';
                                $tdс_class = '';
                                ?>
                                <tr>
                                    <td colspan="6" class="text-right" data-npp="{{$npp++}}">Итого:</td>
                                    <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totInpSum,2)}}</td>
                                    <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totOutSum,2)}}</td>
                                </tr>
                            @endif
                            </tbody>
                            <tfoot>
                        </table>
                    @endif

                    @if(1==1 and isset($recs4))
                        {{-- 2024-02-18 Отчет по отгрузкам со склада --}}
                        <br>
                        <h4 class="text-center">Поступление и отгрузки со склада</h4>
                        <table class="table table-sm table-striped rep-data mt-3"
                               style="background-color: snow; font-size:16px; width:960px"
                               align=center>
                            <thead>
                            <tr class="text-left small" style="vertical-align:middle;">
                                <td class="text-right small" style="width: 38px">№п/п</td>
                                <td class="text-center">Владелец</td>
                                <td class="text-center">Контрагент(Получатель)</td>
                                <td class="text-center">Наименование продукции</td>
                                <td class="text-center">ЕИ</td>
                                <td class="text-right">Кол-во, ЕИ</td>
                                <td class="text-right">Цена, &#8381;</td>
                                <td class="text-right">Сумма, &#8381;</td>
                            </tr>
                            </thead>

                            <tbody>
                            <?php
                            $npp = 0;
                            $totSum = $totInpSum = $totOutSum = $curOrgsSum = $curSum = 0;
                            $curOwnOrgID = $curOrgID = -1;
                            $curDate = '';
                            $curForStock = '';
                            $tstyle = '';
                            $curTrStyle = '';
                            ?>
                            @foreach($recs4 as $rec)
                                @if($rec->docdate <> $curDate or $rec->forstock <> $curForStock)
                                    @if($curDate<>'' and $curSum <> 0)
                                        <tr>
                                            <td colspan="7" class="text-right">Итого по разделу ({{$curForStock_name}}
                                                ):
                                            </td>
                                            <td class="text-right font-weight-bold">{{number_format($curSum,2)}}
                                                &#8381;
                                            </td>
                                        </tr>
                                    @endif
                                    <?php
                                    $npp = 0;
                                    $curSum = 0;
                                    $curDate = $rec->docdate;
                                    $curForStock = $rec->forstock;
                                    if ($curForStock == 1) {
                                        $curForStock_name = 'Поступление на склад';
                                        $curTrStyle = 'background-color:aliceblue;';
                                    } else {
                                        $curForStock_name = 'Отгрузка со склада';
                                        $curTrStyle = 'background-color:#edffdb;';
                                    }
                                    ?>
                                    <tr style="{{$curTrStyle}}">
                                        <td colspan="8">{{date_create($rec->docdate)->format('d.m.y')}}:
                                            <b>{{$curForStock_name}}</b></td>
                                    </tr>
                                @endif
                                @if ($rec->orgid <> $curOrgID or $rec->ownorgid <> $curOwnOrgID)
                                    @if($curOrgID<>-1 and $curOwnOrgID<>-1)
                                        <tr>
                                            <td colspan="7" class="text-right">Итого по клиенту:
                                            </td>
                                            <td class="text-right font-weight-bold">{{number_format($curOrgsSum,2)}}
                                                &#8381;
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td colspan="8" class="text-centerwr">"{{$rec->ownorg_name}}" ->
                                            "{{$rec->org_name}}"
                                            @if(isset($rec->orgid) and $rec->orgid <> $rec->ownorgid)
                                                ,  сальдо за дату: <b>{{number_format($rec->org_saldo,2)}}</b>
                                                <a href="{{route('reports.rep48', [$rec->ownorgid, $rec->orgid])}}"
                                                   target="_blank" title="Подробности">...</a>
                                            @endif
                                        </td>
                                    </tr>
                                    <?php
                                    $curOrgID = $rec->orgid;
                                    $curOwnOrgID = $rec->ownorgid;
                                    $npp = 0;
                                    $curOrgsSum = 0;
                                    ?>
                                @endif
                                <?php
                                $tr_class = "";
                                $td_class = "";
                                $tdс_class = "";

                                $qty = (isset($rec->qty)) ? number_format($rec->qty, 0) : '';
                                $sum = (isset($rec->sum)) ? number_format($rec->sum, 2) : '';


                                $n_qty = (isset($rec->qty)) ? $rec->qty : 0;
                                $n_sum = (isset($rec->sum)) ? $rec->sum : 0;

                                $ref_url = null;
                                $inp_ref_url = null;
                                $sale_ref_url = null;

                                //$tstyle = ($rec->qty > 0) ? 'background-color:#ffff94' : '';
                                ?>
                                <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                                    <td class="text-right small ">
                                        {{++$npp}}
                                    </td>
                                    <td class="text-left small" data-npp="{{$npp}}">
{{--                                        {{$rec->ownorg_name}}--}}
                                    </td>
                                    <td class="text-left small" data-npp="{{$npp}}">
{{--                                        {{$rec->org_name}}--}}
                                    </td>
                                    <td class="text-left small" data-npp="{{$npp}}">
                                        {{$rec->refitm_name}}
                                    </td>
                                    <td class="text-center small">
                                        {{$rec->refitm_unit}}
                                    </td>
                                    <td class="text-right small calced" data-num="{{$n_qty}}">
                                        {{$qty}}
                                    </td>
                                    <td class="text-right small">
                                        {{$rec->price}}
                                    </td>
                                    <td class="text-right small calced" data-num="{{$n_sum}}">
                                        {{$sum}}
                                    </td>
                                </tr>
                                <?php
                                $curSum += $rec->sum;
                                if ($rec->forstock == 1) {
                                    $totInpSum += $rec->sum;
                                    $curOrgsSum += $rec->sum;
                                } else {
                                    $totOutSum += $rec->sum;
                                    $curOrgsSum -= $rec->sum;
                                }
                                ?>
                            @endforeach
                            @if($curOrgID<>-1 and $curOwnOrgID<>-1)
                                <tr>
                                    <td colspan="7" class="text-right">Итого по клиенту:
                                    </td>
                                    <td class="text-right font-weight-bold">{{number_format($curOrgsSum,2)}}
                                        &#8381;
                                    </td>
                                </tr>
                            @endif
                            @if($curDate<>'' and $curSum <> 0)
                                <tr>
                                    <td colspan="7" class="text-right">Итого по разделу ({{$curForStock_name}}):</td>
                                    <td class="text-right font-weight-bold">{{number_format($curSum,2)}} &#8381;</td>
                                </tr>
                            @endif
                            @if(1==1)
                                <?php
                                $td_class = '';
                                $tdс_class = '';
                                ?>
                                <tr style="background-color:aliceblue;">
                                    <td colspan="7" class="text-right" data-npp="{{$npp++}}">Всего "Поступление на
                                        склад":
                                    </td>
                                    <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totInpSum,2)}}
                                        &#8381;
                                    </td>
                                </tr>
                                <tr style="background-color:#edffdb;">
                                    <td colspan="7" class="text-right" data-npp="{{$npp++}}">Всего "Отгрузка со
                                        склада":
                                    </td>
                                    <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totOutSum,2)}}
                                        &#8381;
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                            <tfoot>
                        </table>

                    @endif
                </div>
            @endif
        @endif

    </div>

    <script src="{{ asset('js/rep46.js') }}" defer></script>

@endsection
