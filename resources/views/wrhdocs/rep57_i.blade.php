@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 57;
//$retURL = route('admin') . '#nsi-rep';
$retURL = $data->returl ?? route('paydocs.index');

$report = \App\report::find($thisObjId);
//dd($report);
if (!isset($report))
    return redirect($retURL);

$thisTitle = "Расшифровка прихода на склад";
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
                    <br>Товарная позиция: <b>{{$data->refitm_name}}</b>, ЕИ: <b>{{$data->refitm_unit}}</b>
                    <br>в период: <b>{{date_create($search_params['s_begdate']??'')->format('d.m.Y')}}
                        - {{date_create($search_params['s_enddate']??'')->format('d.m.Y')}}</b>
                    <div class="font-weight-bold">{{$data->aux_info}}</div>
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
                       class="table table-sm table-striped rep-data mt-3 " align="center"
                       style="background-color: snow; font-size:16px; max-width:960px; align-self: center">
                    <thead>
                    <tr class="text-left small" style="vertical-align:middle;">
                        <td class="text-right small" style="width: 38px">№п/п</td>
                        <td class="text-center">Дата</td>
                        <td class="text-right">Кол-во, ЕИ</td>
                        <td class="text-right">Цена, руб</td>
                        <td class="text-right">Сумма, руб</td>
                        <td class="text-left">Основание</td>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $npp = 0;
                    $totSum = $totQty = 0;
                    ?>
                    @foreach($recs as $rec)
                        <?php

                        $tr_class = "";
                        $td_class = "";
                        $tdс_class = "";

                        $qty = (isset($rec->qty)) ? number_format($rec->qty, 0) : '';
                        $price = (isset($rec->price)) ? number_format($rec->price, 2) : '';
                        $sum = (isset($rec->sum)) ? number_format($rec->sum, 2) : '';


                        $n_qty = (isset($rec->qty)) ? $rec->qty : 0;
                        $n_sum = (isset($rec->sum)) ? $rec->sum : 0;

                        $tstyle = ($rec->qty > 0) ? 'background-color:#ffff94' : '';
                        ?>
                        <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                            <td class="text-right small ">
                                {{++$npp}}
                            </td>
                            <td class="text-center small" data-npp="{{$npp}}">
                                {{date_create($rec->docdate)->format('d.m.Y')}}
                            </td>
                            <td class="text-right  calced" data-num="{{$n_qty}}">
                                {{$qty}}
                            </td>
                            <td class="text-right small calced0" data-num="{{0}}">
                                {{$price}}
                            </td>
                            <td class="text-right calced" data-num="{{$n_sum}}">
                                {{$sum}}
                            </td>
                            <td class="text-ledt small">
                                <a href="{{route("wrhdocs.edit",$rec->docid)}}" target="_blank">{{$rec->doctype_name}} № {{$rec->docnum}}</a> {{$rec->org_name}}
                            </td>
                        </tr>
                        <?php
                        $totQty += $rec->qty;
                        $totSum += $rec->sum;
                        ?>
                    @endforeach
                    @if(1==1)
                        <?php
                        $td_class = '';
                        $tdс_class = '';
                        ?>
                        <tr>
                            <td colspan="2" class="text-right" data-npp="{{$npp++}}">Итого:</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totQty,0)}}</td>
                            <td></td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totSum,2)}}</td>
                        </tr>
                    @endif
                    </tbody>
                    <tfoot>
                </table>

            </div>
        @endif

    </div>

    {{--    <script src="{{ asset('js/rep53.js') }}" defer></script>--}}

@endsection
