@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 65;
$retURL = $data->returl??route('reports.pub_index');

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
        @media print {
            @page {
                size: A4 landscape;
                /*size: portrait;*/
            //size:auto;   /* auto is the initial value */
                /* this affects the margin in the printer settings */
                margin: 5mm 5mm 5mm 5mm;
            }

            .pagebreak {
                page-break-inside: avoid;
                page-break-before: always;
            }
        }

        body {
            /* this affects the margin on the content before sending to printer */
            margin: 0px;
        }

        .rep-data td {
            /*padding: 5px;*/
            border-collapse: collapse;
            border: 1px solid #e2e2e2;
        }

        .page {
            background-color: white;
        }

        .day {
            text-align: center;
        }

        .work {
            background-color: #e5ffbe;
            font-size: 9px;
            text-align: right;

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
                                    @if(1==0)
                                        <div class="form-group col-md-2 dpt_1" style="">
                                            <label for="s_month" class="required">Месяц:</label>
                                            {!! Form::select('s_month', $data->monthes??[], $search_params['s_month'],
                                                            [
                                                            'id' => 's_month',
                                                            'class' => 'form-control',
                                                            'placeholder' => '-укажите-',
                                                            ])
                                                            !!}
                                        </div>

                                        <div class="form-group col-md-2 dpt_3 " style="">
                                            <label for="s_year" class="required">Год:</label>
                                            {!! Form::select('s_year', $data->years??[], $search_params['s_year'],
                                                            [
                                                            'id' => 's_year',
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
                                        <div class="form-group col-md-3">
                                            <label for="s_categoryid">Категория:</label>
                                            {!! Form::select('s_itmtypeid', $data->itmtypes, $search_params['s_itmtypeid']??'',
                                                            [
                                                            'class' => 'form-control',
                                                            'placeholder' => '-все-',
                                                            ])
                                                            !!}
                                        </div>

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
            @if (count($recs)==0)

                <div class="page p-3 d-print-none" align="center">
                    нет операций для заданных значений
                </div>

            @else
                <?php
                //                $s_period_type = $search_params['s_period_type'] ?? '';
                //                $ownorgid = $search_params['s_ownorgid'] ?? '';
                $s_begdate = $search_params['s_begdate'] ?? '';
                $s_enddate = $search_params['s_enddate'] ?? '';

                $month_days = (strtotime($s_enddate) - strtotime($s_begdate)) / 3600 / 24 + 1;
                //dd($month_days);

                //                $base_pre_date = date_create($s_begdate)->sub(new DateInterval('P1D'))->format('Y-m-d');
                //dd($pre_date);
                ?>

                <div class="page p-2 container-fluid">

                    <span class="float-right d-print-none">
                        <a class="btn btn-warning btn-sm print-window d-print-none "
                           onclick="window.print();"
                           title="печать">
                            <i class="fa fa-print" aria-hidden="true"></i>
                        </a>
                        @if(1==0)
                            <a class="btn btn-success btn-sm mr-3"
                               href="{{ route('reports.rep43_excel')  }}" title="Выгрузить результаты в Excel">
                                        <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                                    </a>
                        @endif
                        <a class="btn btn-close btn-info btn-sm d-print-none"
                           href="{{ $retURL }}"><i class="fa fa-times" aria-hidden="true"></i></a>

                    </span>

                    <div class="font-weight-bold mt-2" align="center"
                         style="font-size: 14px;">
                        <h4>{{$thisTitle}}</h4>
                        {{--                            {{$data->ownorgs[$search_params['s_ownorgid']]??''}}--}}
                        {{--                        {{$data->monthes[$search_params['s_month']]??''}} {{$search_params['s_year']??''}}--}}
                        Период: {{date_format(date_create($s_begdate),'d.m.Y')}}
                        - {{date_format(date_create($s_enddate),'d.m.Y')}}
                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>
                    </div>

                    {{-- ------------------------------------------------------------------------------------------}}

                    <table class="table table-bordered table-sm table-data" border="0" style="background-color: white">
                        <tr>
                            <td class="small text-right">#пп</td>
                            <td class="text-center">Дата</td>
                            <td class="text-center">Произведено, руб</td>
                            <td class="text-center">Затрачено, руб</td>
                            <td class="text-center">Баланс, руб</td>
                            <td class="text-center">Реализация, руб</td>
                        </tr>
                        <?php
                        $npp = 0;
                        $tot_inp_sum = 0;
                        $tot_out_sum = 0;
                        $tot_blns_sum = 0;
                        $tot_sale_sum = 0;
                        ?>
                        @foreach($recs as $itm)
                            <tr>
                                <td class="small text-right">{{++$npp}}</td>
                                <td class="text-center">
                                    <a href="{{route('reports.rep66',['date'=>$itm->operdate])}}?returl={{$retURL}}"
                                       target="_blank">
                                        {{date_format(date_create($itm->operdate),'d.m.Y')}}</a>
                                </td>
                                <td class="text-right">{{number_format($itm->inp_sum,2)}}</td>
                                <td class="text-right">{{number_format($itm->out_sum,2)}}</td>
                                <td class="text-right">{{number_format($itm->blns_sum,2)}}</td>
                                <td class="text-right"><a href="{{route('reports.rep67',['date'=>$itm->operdate])}}?returl={{$retURL}}"
                                                          target="_blank">{{number_format($itm->sale_sum,2)}}</a></td>
                            </tr>
                            <?php
                            $tot_inp_sum += $itm->inp_sum;
                            $tot_out_sum += $itm->out_sum;
                            $tot_blns_sum += $itm->inp_sum - $itm->out_sum;
                            $tot_sale_sum += $itm->sale_sum;
                            ?>
                        @endforeach

                        <tr>
                            <td colspan="2" class="text-right">Итого:</td>
                            <td class="text-right font-weight-bold">{{number_format($tot_inp_sum,2)}}</td>
                            <td class="text-right font-weight-bold">{{number_format($tot_out_sum,2)}}</td>
                            <td class="text-right font-weight-bold">{{number_format($tot_blns_sum,2)}}</td>
                            <td class="text-right font-weight-bold">{{number_format($tot_sale_sum,2)}}</td>
                        </tr>
                    </table>
                    {{-- ------------------------------------------------------------------------------------------}}

                </div>
            @endif
        @endif

    </div>

    {{--    <script src="{{ asset('js/rep43.js') }}" defer></script>--}}

@endsection
