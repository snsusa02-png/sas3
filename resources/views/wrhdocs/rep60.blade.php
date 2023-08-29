@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 60;
//$retURL = route('admin') . '#nsi-rep';
$retURL = $data->returl ?? route('paydocs.index');

$report = \App\report::find($thisObjId);
//dd($report);
if (!isset($report))
    return redirect($retURL);

$thisTitle = $report->title ?? $report->name;
$action_url = route('reports.rep' . $thisObjId);

$userid = \Auth::user()->id;
$usrrights = [];
$usrrights['link_tasks'] = false; //\App\usrsysright::isUserHasRightByCode_cached($userid, 'tasks.create');

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

                                    <div class="offset-md-0 col-md-4 ">
                                        <div class="form-group">
                                            <label for="lname">Контрагент:</label>
                                            {{--                                                {!! Form::select('s_orgid', $data->orgs??[], $data->search_params['s_orgid']??'',--}}
                                            {!! Form::select('s_orgid', $data->orgs??[], $search_params['s_orgid']??'',
                                                [
                                                'class' => 'form-control small',
                                                'placeholder' => '-все-',
                                                'id' => 's_orgid',
                                                'onchange' => 'form.submit()',
                                                ])
                                            !!}
                                        </div>
                                    </div>

                                    @if(1==1)
                                        <div class="form-group col-md-2">
                                            <label for="s_begdate">Начало периода:</label>
                                            <input type="date" class="form-control text-center"
                                                   name="s_begdate"
                                                   value="{{$search_params['s_begdate']??''}}"
                                            />
                                        </div>

                                        <div class="form-group col-md-2">
                                            <label for="s_begdate">Окончание периода:</label>
                                            <input type="date" class="form-control text-center"
                                                   name="s_enddate"
                                                   value="{{$search_params['s_enddate']??''}}"
                                            />
                                        </div>
                                    @endif

                                    @if(1==0)
                                        <div class="form-group col-md-2 dpt_1" style="">
                                            <label for="s_month" class="required">Год-Месяц:</label>
                                            {!! Form::select('s_ym', $data->yms??[], $search_params['s_ym'],
                                                            [
                                                            'id' => 's_ym',
                                                            'class' => 'form-control',
                                                            'placeholder' => '-укажите-',
                                                            ])
                                                            !!}
                                        </div>
                                    @endif

                                    @if(1==1)
                                        <div class="form-group col-md-3">
                                            <label for="s_ownorgid" class="">Склад от:</label>
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

                                <div class="row">
                                    <div class="offset-md-6 col-md-5 ">
                                        <div class="form-group">
                                            <label for="lname">Товар:</label>
                                            {!! Form::select('s_refitmid', $data->refitems??[], $search_params['s_refitmid']??'',
                                                [
                                                'class' => 'form-control small',
                                                'placeholder' => '-все-',
                                                'id' => 's_orgid',
                                                ])
                                            !!}
                                        </div>
                                    </div>
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
                       onclick="window.print();"
                       title="печать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>
                    @if(1==0)
                        <a class="btn btn-success btn-sm mr-3"
                           href="{{ route('reports.rep54') }}?xls=1"
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
                    <div>{!! $data->repInfo !!}</div>
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
                    <tr class="text-left small" style="vertical-align:middle;">
                        <td class="text-center">Дата</td>
                        <td class="text-right small" style="width: 38px">№п/п</td>
                        <td class="text-center">Наименование продукции</td>
                        <td class="text-center">ЕИ</td>
                        <td class="text-right">Цена, руб</td>
                        <td class="text-right">Кол-во, ЕИ</td>
                        <td class="text-right">Сумма, руб</td>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $npp = 0;
                    $cur_ownorgid = -1;
                    $cur_date = '-1';
                    $totSum = $totOwnOrgSum = $totDaySum = $totQty = 0;
                    $s_refitmid = $search_params['s_refitmid'] ?? -1;
                    ?>
                    @foreach($recs as $rec)
                        @if ($rec->ownorgid <> $cur_ownorgid)

                            @if ($cur_date<>'-1')
                                <tr>
                                    <td colspan="6" class="text-right font-italic">Итого
                                        за {{date_create($cur_date)->format('d.m.Y')}}:
                                    </td>
                                    <td class="text-right">{{number_format($totDaySum, 2)}}</td>
                                </tr>
                                @php($cur_date = '-1')
                            @endif

                            @if ($cur_ownorgid<>-1)
                                <tr>
                                    <td colspan="6" class="text-right font-italic">Итого
                                        от "{{$cur_ownorg_name}}":
                                    </td>
                                    <td class="text-right">{{number_format($totOwnOrgSum, 2)}}</td>
                                </tr>
                                @php($cur_date = '-1')
                            @endif

                            <tr>
                                <td colspan="7">склад: <b>{{$rec->ownorg_name}}</b></td>
                            </tr>
                            <?php
                            $cur_ownorgid = $rec->ownorgid;
                            $cur_ownorg_name = $rec->ownorg_name;
                            $totOwnOrgSum = 0;
                            $cur_date = "-1";
                            ?>
                        @endif
                        @if ($rec->docdate <> $cur_date)
                            @if ($cur_date<>'-1')
                                <tr>
                                    <td colspan="6" class="text-right font-italic">Итого
                                        за {{date_create($cur_date)->format('d.m.Y')}}:
                                    </td>
                                    <td class="text-right font-italic">{{number_format($totDaySum, 2)}}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="7" class="font-italic">{{date_create($rec->docdate)->format('d.m.Y')}}</td>
                            </tr>
                            <?php
                            $cur_date = $rec->docdate;
                            $npp = 0;
                            $totDaySum = 0;
                            ?>
                        @endif
                        <?php

                        $tr_class = "";
                        $td_class = "";
                        $tdс_class = "";

                        $qty = (isset($rec->qty)) ? number_format($rec->qty, 0) : '';
                        $sale_sum = (isset($rec->sale_sum)) ? number_format($rec->sale_sum, 2) : '';

                        $n_qty = (isset($rec->qty)) ? $rec->qty : 0;
                        $n_sale_sum = (isset($rec->sale_sum)) ? $rec->sale_sum : 0;

                        if ($rec->refitmid == $s_refitmid) {
                            $totQty += $n_qty;
                        }
                        //                            if ($rec->sysobjid == 520)
                        if (1 == 1)
                            $ref_url = null; //route('paydocs.edit', $rec->objid);
                        else
                            $ref_url = null;

                        if ($n_sale_sum > 0)
                            $sale_ref_url = null; //route('paydocs.edit', $rec->objid);
                        else
                            $sale_ref_url = null;


                        $tstyle = ($rec->qty > 0) ? 'background-color:#ffff94' : '';
                        ?>
                        <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                            <td class="text-right small " colspan="2">
                                {{++$npp}}
                            </td>
                            <td class="text-left " data-npp="{{$npp}}">
                                {{$rec->refitm_name}}
                            </td>
                            <td class="text-center small">
                                {{$rec->refitm_unit}}
                            </td>
                            <td class="text-right small">
                                {{number_format($rec->price,2)}}
                            </td>
                            <td class="text-right calced" data-num="{{$n_qty}}">
                                @if(isset($ref_url))
                                    <a href="{{$ref_url}}" target="_blank">{{$qty}}</a>
                                @else
                                    {{$qty}}
                                @endif
                            </td>
                            <td class="text-right calced" data-num="{{$n_sale_sum}}">
                                @if(isset($sale_ref_url))
                                    <a href="{{$sale_ref_url}}" target="_blank">{{$sale_sum}}</a>
                                @else
                                    {{$sale_sum}}
                                @endif
                            </td>
                        </tr>
                        <?php
                        $totDaySum += $rec->sale_sum;
                        $totOwnOrgSum += $rec->sale_sum;
                        $totSum += $rec->sale_sum;
                        ?>
                    @endforeach
                    @if(1==1)
                        <?php
                        if ($s_refitmid == -1)
                            $totQty = '';

                        $td_class = '';
                        $tdс_class = '';
                        ?>
                        @if ($cur_date<>'-1')
                            <tr>
                                <td colspan="6" class="text-right font-italic">Итого
                                    за {{date_create($cur_date)->format('d.m.Y')}}:
                                </td>
                                <td class="text-right font-italic">{{number_format($totDaySum, 2)}}</td>
                            </tr>
                        @endif

                        @if ($cur_ownorgid<>-1)
                            <tr>
                                <td colspan="6" class="text-right font-italic">Итого
                                    от "{{$cur_ownorg_name}}":
                                </td>
                                <td class="text-right">{{number_format($totOwnOrgSum, 2)}}</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="5" class="text-right" data-npp="{{$npp++}}">Всего:</td>
                            <td class="text-right font-weight-bold">{{$totQty}}</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totSum,2)}}</td>
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
