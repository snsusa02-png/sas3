@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 55;
//$retURL = route('admin') . '#nsi-rep';
$retURL = $data->returl ?? route('paydocs.index');

$report = \App\report::find($thisObjId);

if (!isset($report))
    return redirect($retURL);

$thisTitle = $report->title ?? $report->name;
//$action_url = route('reports.rep' . $thisObjId);

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

        @if (isset($recs))

            <div class="page p-2 container-fluid">
                <div id="_calc_selected_sum"
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
                           href="{{ route('reports.rep54',['date'=>$data->date]) }}?xls=1"
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
                    <b>{{date_create($data->date)->format('d.m.Y')}}</b>
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
                        <td class="text-right small" style="width: 38px">№п/п</td>
                        <td class="text-center">Наименование продукции</td>
                        <td class="text-center">ЕИ</td>
                        <td class="text-right">Вх. остаток, ЕИ</td>
                        <td class="text-right">Приход, ЕИ</td>
                        <td class="text-right">Расход, ЕИ</td>
                        <td class="text-right">Отгрузка, руб</td>
                        <td class="text-right">Исх. остаток, ЕИ</td>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $npp = 0;
                    $totSum = $totInpSum = $totOutSum = 0;
                    ?>
                    @foreach($recs as $rec)
                        <?php

                        $tr_class = "";
                        $td_class = "";
                        $tdс_class = "";

                        $pre_qty = (isset($rec->pre_qty)) ? number_format($rec->pre_qty, 0) : '';
                        $inp_qty = (isset($rec->inp_qty)) ? number_format($rec->inp_qty, 0) : '';
                        $out_qty = (isset($rec->out_qty)) ? number_format($rec->out_qty, 0) : '';
                        $sale_sum = (isset($rec->sale_sum)) ? number_format($rec->sale_sum, 2) : '';


                        $n_pre_qty = (isset($rec->pre_qty)) ? $rec->pre_qty : 0;
                        $n_inp_qty = (isset($rec->inp_qty)) ? $rec->inp_qty : 0;
                        $n_out_qty = (isset($rec->out_qty)) ? $rec->out_qty : 0;
                        $n_sale_sum = (isset($rec->sale_sum)) ? $rec->sale_sum : 0;

                        $n_end_qty = $rec->pre_qty + $rec->inp_qty - $rec->out_qty;
                        $end_qty = number_format($n_end_qty, 0);

                        //                            if ($rec->sysobjid == 520)
                        if (1 == 1)
                            $ref_url = null; //route('paydocs.edit', $rec->objid);
                        else
                            $ref_url = null;

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
                            <td class="text-right small calced" data-num="{{$n_inp_qty}}">
                                @if(isset($ref_url))
                                    <a href="{{$ref_url}}" target="_blank">{{$inp_qty}}</a>
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
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totOutSum,2)}}</td>
                        </tr>
                    @endif
                    </tbody>
                    <tfoot>
                </table>

                @if(isset($recs2))
                    <h4>По контрагентам</h4>
                    <table id="results2"
                           class="table table-sm table-striped rep-data mt-3"
                           style="background-color: snow; font-size:16px; max-width:960px; align-self: center">
                        <thead>
                        <tr class="text-left small" style="vertical-align:middle;">
                            <td class="text-right small" style="width: 38px">№п/п</td>
                            <td class="text-left">Контрагент</td>
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
                        $totSum = 0;
                        $cur_orgid = -1;
                        ?>
                        @foreach($recs2 as $rec)
                            <?php

                            $tr_class = "";
                            $td_class = "";
                            $tdс_class = "";

                            $tstyle = '';
                            ?>
                            @if($rec->orgid <> $cur_orgid)
                                <tr>
                                    <td></td>
                                    <td colspan="6" class="font-weight-bold font-italic">
                                        {{$rec->org_name}}
                                    </td>
                                </tr>
                                <?php
                                $cur_orgid = $rec->orgid;
                                ?>
                            @endif
                            <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                                <td class="text-right small ">
                                    {{++$npp}}
                                </td>
                                <td class="text-left " data-npp="{{$npp}}">
                                </td>
                                <td class="text-left " data-npp="{{$npp}}">
                                    {{$rec->refitm_name}}
                                </td>
                                <td class="text-center small">
                                    {{$rec->refitm_unit}}
                                </td>
                                <td class="text-right small calced" data-num="{{$rec->qty}}">
                                    {{number_format($rec->qty, 0)}}
                                </td>
                                <td class="text-right small calced" data-num="{{$rec->price}}">
                                    {{number_format($rec->price, 2)}}
                                </td>
                                <td class="text-right small calced" data-num="{{$rec->itm_sum}}">
                                    {{number_format($rec->itm_sum, 2)}}
                                </td>
                            </tr>
                            <?php
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
                                <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totOutSum,2)}}</td>
                            </tr>
                        @endif
                        </tbody>
                        <tfoot>
                    </table>

                @endif
            </div>
        @endif

    </div>

    <script src="{{ asset('js/rep53.js') }}" defer></script>

@endsection
