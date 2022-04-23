@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 54;
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
                        <td class="text-center">от ГК</td>
                        <td class="text-center">Контрагент</td>
                        <td class="text-right">Приход, руб</td>
                        <td class="text-right">Расход, руб</td>
                        <td class="text-center">Основание</td>
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

                        $inp_sum = (isset($rec->inp_sum)) ? number_format($rec->inp_sum, 2) : '';
                        $out_sum = (isset($rec->out_sum)) ? number_format($rec->out_sum, 2) : '';

                        $n_inp_sum = (isset($rec->inp_sum)) ? $rec->inp_sum : 0;
                        $n_out_sum = (isset($rec->out_sum)) ? $rec->out_sum : 0;

                        //                            if ($rec->sysobjid == 520)
                        if (1 == 1)
                            $ref_url = route('paydocs.edit', $rec->objid);
                        else
                            $ref_url = null;

                        $tstyle = ($rec->sumtypeid == 1) ? 'background-color:#ffff94' : '';
                        ?>
                        <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                            <td class="text-left small" data-npp="{{$npp++}}">
                                {{$rec->ownorg_name}}
                            </td>
                            <td class="text-left small">
                                {{$rec->org_name}}
                            </td>
                            <td class="text-right small calced" data-num="{{$n_inp_sum}}">
                                @if(isset($ref_url))
                                    <a href="{{$ref_url}}" target="_blank">{{$inp_sum}}</a>
                                @else
                                    {{$inp_sum}}
                                @endif
                            </td>
                            <td class="text-right small calced" data-num="-{{$n_out_sum}}">
                                @if(isset($ref_url))
                                    <a href="{{$ref_url}}" target="_blank">{{$out_sum}}</a>
                                @else
                                    {{$out_sum}}
                                @endif
                            </td>
                            <td class="text-left small">
                                {{$rec->descript}}
                            </td>
                        </tr>
                        <?php
                        $totSum += $rec->paysum;
                        $totInpSum += $rec->inp_sum;
                        $totOutSum += $rec->out_sum;
                        ?>
                    @endforeach
                    @if(1==1)
                        <?php
                        $td_class = '';
                        $tdс_class = '';
                        ?>
                        <tr>
                            <td colspan="2" class="text-right" data-npp="{{$npp++}}">Итого:</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totInpSum,2)}}</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totOutSum,2)}}</td>
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
