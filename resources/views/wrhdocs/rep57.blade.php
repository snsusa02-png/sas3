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

                                    @if(1==1)
                                        <div class="form-group col-md-3">
                                            <label for="s_begdate">Начало периода:</label>
                                            <input type="date" class="form-control text-center"
                                                   name="s_begdate"
                                                   value="{{$search_params['s_begdate']??''}}"
                                            />
                                        </div>

                                        <div class="form-group col-md-3">
                                            <label for="s_begdate">Окончание периода:</label>
                                            <input type="date" class="form-control text-center"
                                                   name="s_enddate"
                                                   value="{{$search_params['s_enddate']??''}}"
                                            />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="s_categoryid">Категория:</label>
                                            {!! Form::select('s_itmtypeid', $data->itmtypes, $search_params['s_itmtypeid']??'',
                                                            [
                                                            'class' => 'form-control',
                                                            'placeholder' => '-все-',
                                                            ])
                                                            !!}
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

                                    @if(1==0)
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
                    <b>{{date_create($search_params['s_begdate']??'')->format('d.m.Y')}}
                        - {{date_create($search_params['s_enddate']??'')->format('d.m.Y')}}</b>
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
                        <td class="text-right">Вх. остаток, ЕИ / руб</td>
                        <td class="text-right">Приход, ЕИ / руб</td>
                        <td class="text-right">Расход, ЕИ / руб</td>
                        <td class="text-right">Исх. остаток, ЕИ / руб</td>
                        <td class="text-right">Рeализация, ЕИ / руб</td>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $npp = 0;
                    $totSum = $totPreSum = $totInpSum = $totOutSum = $totSaleSum = $totCurSum= 0;
                    ?>
                    @foreach($recs as $rec)
                        <?php

                        $tr_class = "";
                        $td_class = "";
                        $tdс_class = "";

                        $pre_qty = (isset($rec->pre_qty)) ? number_format($rec->pre_qty, 0) : '';
                        $pre_sum = (isset($rec->pre_sum)) ? number_format($rec->pre_sum, 2) : '';
                        $inp_qty = (isset($rec->inp_qty)) ? number_format($rec->inp_qty, 0) : '';
                        $out_qty = (isset($rec->out_qty)) ? number_format($rec->out_qty, 0) : '';
                        $inp_sum = (isset($rec->inp_sum)) ? number_format($rec->inp_sum, 2) : '';
                        $out_sum = (isset($rec->out_sum)) ? number_format($rec->out_sum, 2) : '';
                        $sale_qty = (isset($rec->sale_qty)) ? number_format($rec->sale_qty, 0) : '';
                        $sale_sum = (isset($rec->sale_sum)) ? number_format($rec->sale_sum, 2) : '';
                        $cur_qty = (isset($rec->cur_qty)) ? number_format($rec->cur_qty, 0) : '';
                        $cur_sum = (isset($rec->cur_sum)) ? number_format($rec->cur_sum, 2) : '';


                        $n_pre_qty = (isset($rec->pre_qty)) ? $rec->pre_qty : 0;
                        $n_pre_sum = (isset($rec->pre_sum)) ? $rec->pre_sum : 0;

                        $n_inp_qty = (isset($rec->inp_qty)) ? $rec->inp_qty : 0;
                        $n_inp_sum = (isset($rec->inp_sum)) ? $rec->inp_sum : 0;

                        $n_out_qty = (isset($rec->out_qty)) ? $rec->out_qty : 0;
                        $n_out_sum = (isset($rec->out_sum)) ? $rec->out_sum : 0;

                        $n_sale_qty = (isset($rec->sale_qty)) ? $rec->sale_qty : 0;
                        $n_sale_sum = (isset($rec->sale_sum)) ? $rec->sale_sum : 0;

                        $n_cur_qty = (isset($rec->cur_qty)) ? $rec->cur_qty : 0;
                        $n_cur_sum = (isset($rec->cur_sum)) ? $rec->cur_sum : 0;

                        //$n_end_qty = $rec->pre_qty + $rec->inp_qty - $rec->out_qty;
                        $n_end_qty = $rec->cur_qty;
                        $end_qty = number_format($n_end_qty, 0);

                        //$n_end_sum = $rec->pre_sum + $rec->inp_sum - $rec->sale_sum;
                        $n_end_sum = $n_cur_sum;
                        $end_sum = number_format($n_end_sum, 2);

                        //                            if ($rec->sysobjid == 520)
                        if (1 == 1)
                            $ref_url = null; //route('paydocs.edit', $rec->objid);
                        else
                            $ref_url = null;

                        if ($n_pre_qty > 0)
                            $pre_avg_price = round($n_pre_sum/$n_pre_qty,2);
                        else
                            $pre_avg_price = null;

                        if ($n_inp_qty > 0){
                            $inp_ref_url = route('reports.rep57_i') . '?ri_id=' . $rec->refitmid;
                            $inp_avg_price = round($n_inp_sum/$n_inp_qty,2);
                        }
                        else{
                            $inp_ref_url = null;
                            $inp_avg_price = null;
                        }

                        if ($n_out_qty > 0)
                            $out_avg_price = round($n_out_sum/$n_out_qty,2);
                        else
                            $out_avg_price = null;

                        if ($n_sale_sum > 0){
                            $sale_ref_url = null; //route('paydocs.edit', $rec->objid);
                            $sale_avg_price = round($n_sale_sum/$n_sale_qty,2);
                            }
                        else{
                            $sale_ref_url = null;
                            $sale_avg_price = null;
                            }

                        if ($n_end_qty > 0)
                            $end_avg_price = round($n_end_sum/$n_end_qty,2);
                        else
                            $end_avg_price = null;

                        $tstyle = ($rec->inp_qty + $rec->out_qty > 0) ? 'background-color:#ffff94' : '';
                        ?>
                        <tr class="text-left {{$tr_class}}" style="{{$tstyle}}">
                            <td class="text-right small ">
                                {{++$npp}}
                            </td>
                            <td class="text-left small" data-npp="{{$npp}}">
                                {{$rec->refitm_name}}
                            </td>
                            <td class="text-center small">
                                {{$rec->refitm_unit}}
                            </td>
                            <td>
                                <div class="text-right small calced" data-num="{{$n_pre_qty}}">
                                    @if(isset($ref_url))
                                        <a href="{{$ref_url}}" target="_blank">{{$pre_qty}}</a>
                                    @else
                                        {{$pre_qty}}
                                    @endif
                                </div>
                                <div class="text-right small calced" data-num="{{$n_pre_sum}}" title="{{$pre_avg_price}}">
                                    @if(isset($ref_url))
                                        <a href="{{$ref_url}}" target="_blank">{{$pre_sum}}</a>
                                    @else
                                        {{$pre_sum}}
                                    @endif
                                </div>
                                <div class="text-right small text-secondary text-black-50-">
                                    <span class="small" title="средняя цена"> {{$pre_avg_price}}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-right small calced" data-num0="{{$n_inp_qty}}">
                                    @if(isset($inp_ref_url))
                                        <a href="{{$inp_ref_url}}" target="_blank">{{$inp_qty}}</a>
                                    @else
                                        {{$inp_qty}}
                                    @endif
                                </div>
                                <div class="text-right small calced" data-num0="{{$n_inp_sum}}" title="{{$inp_avg_price}}">
                                    @if(isset($inp_ref_url))
                                        <a href="{{$inp_ref_url}}" target="_blank">{{$inp_sum}}</a>
                                    @else
                                        {{$inp_sum}}
                                    @endif
                                </div>
                                <div class="text-right small text-secondary text-black-50-">
                                    <span class="small" title="средняя цена"> {{$inp_avg_price}}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-right small calced" data-num="-{{$n_out_qty}}">
                                    @if(isset($ref_url))
                                        <a href="{{$ref_url}}" target="_blank">{{$out_qty}}</a>
                                    @else
                                        {{$out_qty}}
                                    @endif
                                </div>
                                <div class="text-right small calced" data-num="{{$n_out_sum}}" title="{{$out_avg_price}}">
                                    @if(isset($out_ref_url))
                                        <a href="{{$out_ref_url}}" target="_blank">{{$out_sum}}</a>
                                    @else
                                        {{$out_sum}}
                                    @endif
                                </div>
                                <div class="text-right small text-secondary text-black-50-">
                                    <span class="small" title="средняя цена"> {{$out_avg_price}}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-right small calced" data-num="{{$n_end_qty}}">
                                    {{$end_qty}}
                                </div>
                                <div class="text-right small calced" data-num="{{$n_end_sum}}" title="{{$end_avg_price}}">
                                    {{$end_sum}}
                                </div>
                                <div class="text-right small text-secondary text-black-50-">
                                    <span class="small" title="средняя цена"> {{$end_avg_price}}</span>
                                </div>

                            </td>

                            <td>
                                <div class="text-right small calced" data-num="-{{$n_sale_qty}}">
                                    @if(isset($ref_url))
                                        <a href="{{$ref_url}}" target="_blank">{{$sale_qty}}</a>
                                    @else
                                        {{$sale_qty}}
                                    @endif
                                </div>
                                <div class="text-right small calced" data-num="{{$n_sale_sum}}" title="{{$sale_avg_price}}">
                                    @if(isset($sale_ref_url))
                                        <a href="{{$sale_ref_url}}" target="_blank">{{$sale_sum}}</a>
                                    @else
                                        {{$sale_sum}}
                                    @endif
                                </div>
                                <div class="text-right small text-secondary0 text-black-50">
                                    <span class="small" title="средняя цена"> {{$sale_avg_price}}</span>
                                </div>
                            </td>
                        </tr>
                        <?php
                        $totPreSum += $rec->pre_sum;
                        $totInpSum += $rec->inp_sum;
                        $totOutSum += $rec->out_sum;
                        $totSaleSum += $rec->sale_sum;
                        $totCurSum += $rec->cur_sum;
                        ?>
                    @endforeach
                    @if(1==1)
                        <?php
                        $td_class = '';
                        $tdс_class = '';
                        ?>
                        <tr>
                            <td colspan="3" class="text-right" data-npp="{{$npp++}}">Итого:</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totPreSum,2)}}</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totInpSum,2)}}</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totOutSum,2)}}</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totCurSum,2)}}</td>
                            <td class="text-right font-weight-bold {{$td_class}}">{{number_format($totSaleSum,2)}}</td>
                        </tr>
                    @endif
                    </tbody>
                    <tfoot>
                </table>


                @if(1==0 and isset($recs2))
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
