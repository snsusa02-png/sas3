@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 53;
//$retURL = route('admin') . '#nsi-rep';
$retURL = $data->returl ?? route('paydocs.index');

$report = \App\report::find($thisObjId);

if (!isset($report))
    return redirect($retURL);

$thisTitle = $report->title ?? $report->name;
//$action_url = route('reports.rep' . $thisObjId);

$userid = \Auth::user()->id;
$usrrights = [];
$usrrights['link_tasks'] = \App\usrsysright::isUserHasRightByCode_cached($userid, 'tasks.create');;

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
                        @if(1==1)
                        <a class="btn btn-success btn-sm mr-3"
                           href="{{ route('reports.rep53',['ownorgid'=>$data->ownorg->id,'orgid'=>$data->org->id]) }}?xls=1"
                           title="Выгрузить результаты в Excel">
                                        <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                                    </a>
                    @endif
                        <a class="btn btn-close btn-info btn-sm"
                           href="{{ $retURL  }}">
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </a>
                        </span>

                <div class="mt-2" align="center"
                     style="font-size: 18px;">
                    <h4>{{$thisTitle}}</h4>
                    между <a href="{{route('orgs.edit',$data->org->id)}}"
                             target="_blank"><b>{{$data->org->name??'-'}}</b></a>
                    и <a href="{{route('orgs.edit',$data->ownorg->id)}}"
                         target="_blank"><b>{{$data->ownorg->name??'-'}}</b></a>
                    @if(isset($data->org_saldo->aligmentdate))
                        <div class="small">Взаиморасчеты согласованы с клиентом по
                            <b>{{date_create($data->org_saldo->aligmentdate)->format('d.m.Y')}}</b> включительно
                        </div>
                    @endif
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
                       style="background-color: snow; font-size:16px; max-width:960px"
                       align=center>
                    <thead>
                    <tr class="text-left small" valign="top">
                        <td class="text-center">Дата
                            <button class="btn btn-sm btn-light" id="sort_1" data-dir="1"><i class="fa fa-sort-asc" aria-hidden="true"></i></button>
                        </td>
                        <td class="text-left">Операция</td>
                        <td class="text-right">Кол-во</td>
                        <td class="text-right">Цена,руб</td>
                        <td class="text-right">Сумма, руб</td>
                        <td class="text-right">Тек. сальдо, руб</td>
                    </tr>

                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totSum = $curSum = 0;
                    if (isset($data->org_saldo->aligmentdate))
                        $aligmentdate = date_create($data->org_saldo->aligmentdate);
                    elseif (isset($data->org_saldo) and isset($data->org_saldo->ondate))
                        //$aligmentdate = date_create($data->org_saldo->ondate) + 1;
                        $aligmentdate = $data->org_saldo->ondate;
                    else
                        $aligmentdate = date_create('1970-01-01');
                    ?>
                    @if(isset($data->org_saldo))
                        <?php
                        $td_class = ($data->org_saldo->saldo < 0) ? 'text-danger' : (($data->org_saldo->saldo > 0) ? 'text-success' : '');
                        $curSum += $data->org_saldo->saldo;
                        ?>

                        <tr class="text-left ">
                            <td class="text-center small" data-npp="1">
                                {{date_create($data->org_saldo->ondate)->format('d.m.Y')}}
                            </td>
                            <td class="text-center small font-weight-bold">
                                - начальное сальдо -
                            </td>
                            <td class="text-right "></td>
                            <td class="text-right "></td>
                            <td class="text-right {{$td_class}}"
                                data-num="{{$data->org_saldo->saldo}}">{{number_format($data->org_saldo->saldo,2)}}</td>
                            <td class="text-right small {{$td_class}}">{{number_format($curSum,2)}}</td>
                        </tr>
                        <?php
                        $totSum += $data->org_saldo->saldo;
                        ?>
                    @endif

                    <?php
                    $sumtypes = [1 => 'платеж', 2 => 'поставка'];
                    $cur_operdate = -1;
                    $day_qty = $day_sum = 0;
                    $npp = 2;
                    ?>
                    @foreach($recs as $rec)
                        @if(1==0 and $rec->operdate<>$cur_operdate)
                            @if($cur_operdate<>-1)
                                <tr class="text-left font-italic" style="background-color: #cfebff">
                                    <td class="text-right small " colspan="2" data-npp="{{$npp++}}">
                                        Итого за день:
                                    </td>
                                    <td class=" small text-right">{{number_format($day_qty,1)}}</td>
                                    <td></td>
                                    <td class="text-right">{{number_format($day_sum,2)}}</td>
                                    <td></td>
                                </tr>
                                <?php
                                $day_qty = 0;
                                $day_sum = 0;
                                ?>
                            @endif
                            <tr class="text-left ">
                                <td class="text-left small font-weight-bold" colspan="6" data-npp="{{$npp++}}">
                                    {{date_create($rec->operdate)->format('d.m.Y')}}
                                </td>
                            </tr>
                            <?php
                            $cur_operdate = $rec->operdate;
                            ?>
                        @endif
                        <?php
                        $curSum += $rec->opersum;

                        $tr_class = (date_create($rec->operdate) <= $aligmentdate) ? "table-success" : "";

                        $td_class = ($rec->opersum < 0) ? 'text-danger' : (($rec->opersum > 0) ? 'text-success' : '');
                        $tdс_class = ($curSum < 0) ? 'text-danger' : (($totSum > 0) ? 'text-success' : '');

                        $sh_qty = (isset($rec->qty)) ? number_format($rec->qty, 3) : '';
                        $sh_price = (isset($rec->itm_price)) ? number_format($rec->itm_price, 2) : '';

                        //                            if ($rec->sysobjid == 520)
                        //                                $ref_url = route('paydocs.edit', $rec->objid);
                        //                            else
                        $ref_url = null;

                        $tstyle = ($rec->sumtypeid == 1) ? 'background-color:#ffff94' : '';
                        ?>
                        <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                            <td class="text-center small" data-npp="{{$npp++}}">
                                {{date_create($rec->operdate)->format('d.m.Y')}}
                            </td>
                            <td class="text-left small">
                                <span class="font-weight-bold small"> {{$sumtypes[$rec->sumtypeid]??'?'}}</span>:
                                @if(isset($ref_url))
                                    <a href="{{$ref_url}}" target="_blank">{{$rec->descript}}</a>
                                @else
                                    {{$rec->descript}}
                                @endif
                                <div class="float-right"> {{$rec->org_placename}}</div>
                            </td>
                            <td class="text-right small calced" data-num="{{$rec->qty}}">{{$sh_qty}}</td>
                            <td class="text-right small">{{$sh_price}}</td>
                            <td class="text-right calced {{$td_class}}"
                                data-num="{{$rec->opersum}}">{{number_format($rec->opersum,2)}}</td>
                            <td class="text-right small calced {{$tdс_class}}"
                                data-num="{{$curSum}}">{{number_format($curSum,2)}}</td>
                        </tr>
                        <?php
                        $day_qty += $rec->qty;
                        $day_sum += $rec->opersum;
                        $totSum += $rec->opersum;
                        ?>
                    @endforeach
                    @if($cur_operdate<>-1)
                        <tr class="text-left font-italic" style="background-color: #cfebff">
                            <td class="text-right small " colspan="2" data-npp="{{$npp++}}">
                                Итого за день:
                            </td>
                            <td class=" small text-right calc"
                                data-num="{{$day_qty}}">{{number_format($day_qty,1)}}</td>
                            <td></td>
                            <td class="text-right calc" data-num="{{$day_sum}}>{{number_format($day_sum,2)}}</td>
                                <td></td>
                            </tr>
                            <?php
                            $day_qty = 0;
                            $day_sum = 0;
                            ?>
                            @endif

                            @if(1==1)
                            <?php
                            $td_class = ($totSum < 0) ? 'text-danger' : (($totSum > 0) ? 'text-success' : '');
                            $tdс_class = ($curSum < 0) ? 'text-danger' : (($totSum > 0) ? 'text-success' : '');
                            ?>
                                <tr style=" border-top:1px solid darkred !important;
                            ">
                            <td colspan="4" class="text-right" data-npp="{{$npp++}}">Итого:</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totSum,2)}}</td>
                            <td class="text-right small {{$tdс_class}}">{{number_format($curSum,2)}}</td>
                        </tr>
                    @endif
                    </tbody>
                    <tfoot>
                </table>
                <div class=" bg-white mt-3 p-2">
                    @if( $usrrights['link_tasks']??false )
                        <a href="{{ route('tasks.create')}}?srcsysobjid=111&srcobjid={{$data->org->id}}&returl={{Request::url()}}"
                           class=""
                           title="Создать задачу">
                            <i class="fa fa-plus-circle text-info text-right" aria-hidden="true"></i>
                        </a>
                    @endif
                    @if(1==1 and isset($data->tasks))
                        <label class="small mb-0">Задачи:</label>
                        <ul class="mb-1" style="border-top: 1px solid silver;">
                            @foreach($data->tasks as $tsk)
                                <li><a href="{{route('tasks.edit',$tsk->id)}}?returl={{Request::url()}}"
                                       style1="color: firebrick"
                                       target="_blank">{{  $tsk->name}}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>

            </div>
        @endif

    </div>

    <script src="{{ asset('js/rep53.js') }}" defer></script>

@endsection
