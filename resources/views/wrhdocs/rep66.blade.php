@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 66;
//$retURL = route('admin') . '#nsi-rep';
$retURL = $data->returl ?? route('paydocs.index');

$report = \App\report::find($thisObjId);

if (!isset($report))
    return redirect($retURL);

$thisTitle = $report->title ?? $report->name;
//dd($report, $thisTitle);
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

        .b-r {
            border-right: 1px solid darkgray !important;
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
                        <td class="text-center">Наименование операции/вида затрат</td>
                        <td class="text-center b-r">Расход</td>
                        <td class="text-center b-r">Приход</td>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $npp = 0;
                    $totSum = $totPreSum = $totInpSum = $totOutSum = $totEndSum = 0;
                    $cur_ownorgid = -1;
                    ?>
                    @foreach($recs as $rec)
                        <?php
                        $tr_class = "";
                        $td_class = "";
                        $tdс_class = "";

                        $inp_sum = $out_sum = '';
                        if ($rec->dir < 0){
                            $totOutSum += $rec->sum;
                            $out_sum = $rec->sum;
                        }
                        else{
                            $inp_sum = $rec->sum;
                            $totInpSum += $rec->sum;
                        }

                        //$tstyle = ($rec->inp_qty + $rec->out_qty > 0) ? 'background-color:#ffff94' : '';
                        $tstyle = '';
                        ?>
                        <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                            <td class="text-right small ">
                                {{++$npp}}
                            </td>
                            <td class="text-left " data-npp="{{$npp}}">
                                {{$rec->name}}
                            </td>
                            <td class="text-right">{{$out_sum}}</td>
                            <td class="text-right">{{$inp_sum}}</td>
                        </tr>
                        <?php
                        $totSum += $rec->dir * $rec->sum;
                        ?>
                    @endforeach
                    @if(1==1)
                        <?php
                        $td_class = '';
                        $tdс_class = '';
                        ?>
                        <tr>
                            <td colspan="2" class="text-right" data-npp="{{$npp++}}">Итого:</td>
                            <td class="text-right font-weight-bold b-r {{$td_class}}">{{number_format($totOutSum,2)}}</td>
                            <td class="text-right font-weight-bold b-r {{$td_class}}">{{number_format($totInpSum,2)}}</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-right" data-npp="{{$npp++}}">Всего:</td>
                            <td class="text-center font-weight-bold {{$td_class}}" colspan="2" >{{number_format($totInpSum-$totOutSum,2)}}</td>

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
                        $cur_org_name = "";
                        $org_sum = 0;
                        ?>
                        @foreach($recs2 as $rec)
                            <?php

                            $tr_class = "";
                            $td_class = "";
                            $tdс_class = "";

                            $tstyle = '';
                            ?>
                            @if($rec->orgid <> $cur_orgid)
                                @if($cur_orgid <> -1)
                                    <tr>
                                        <td colspan="6" class="text-right">Итого по "{{$cur_org_name}}":</td>
                                        <td class="text-right font-weight-bold {{$td_class}}">{{number_format($org_sum,2)}}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td></td>
                                    <td colspan="6" class="font-weight-bold font-italic">
                                        {{$rec->org_name}}
                                    </td>
                                </tr>
                                <?php
                                $cur_orgid = $rec->orgid;
                                $cur_org_name = $rec->org_name;
                                $org_sum = 0;
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
                                <td class="text-right small">
                                    {{number_format($rec->price, 2)}}
                                </td>
                                <td class="text-right small calced" data-num="{{$rec->qty}}">
                                    {{number_format($rec->qty, 0)}}
                                </td>
                                <td class="text-right small calced" data-num="{{$rec->itm_sum}}">
                                    {{number_format($rec->itm_sum, 2)}}
                                </td>
                            </tr>
                            <?php
                            $totSum += $rec->itm_sum;
                            $org_sum += $rec->itm_sum;
                            ?>
                        @endforeach
                        @if($cur_orgid <> -1)
                            <tr>
                                <td colspan="6" class="text-right">Итого по "{{$cur_org_name}}":</td>
                                <td class="text-right font-weight-bold {{$td_class}}">{{number_format($org_sum,2)}}</td>
                            </tr>
                        @endif
                        @if(1==1)
                            <?php
                            $td_class = '';
                            $tdс_class = '';
                            ?>
                            <tr>
                                <td colspan="6" class="text-right" data-npp="{{$npp++}}">Итого:</td>
                                <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totSum,2)}}</td>
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
