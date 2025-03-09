@extends('layouts.report')
<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 69;
//$retURL = route('admin') . '#nsi-rep';
//$retURL = '/admin#nsi-rep';
$retURL = route('reports.pub_index');

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
                                        {!! Form::select('s_period_type', $data->period_types??[], $search_params['s_period_type']??'',
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
                                    @if(1==0)
                                        <div class="form-group col-md-3">
                                            <label for="s_ownorgid" class="">Владелец:</label>
                                            {!! Form::select('s_ownorgid', $data->ownorgs??[], $search_params['s_ownorgid'],
                                                            [
                                                            'class' => 'form-control',
                                                            'placeholder' => '-все-',
                                                            ])
                                                            !!}
                                        </div>
                                    @endif
                                    @if(1==0)
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
                                    @endif
                                    @if(1==1)
                                        <div class="form-group col-md-3">
                                            <label for="s_categoryid">Категория продукции:</label>
                                            {!! Form::select('s_itmtypeid', $data->itmtypes, $search_params['s_itmtypeid']??'',
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
            @if (count($recs)==0)

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
                               href="{{ route('reports.rep61')  }}?xls=1" title="Выгрузить результаты в Excel">
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

                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>

                        @if(1==0)
                            <button class="btn btn-primary btn-sm d-print-none" type="button" data-toggle="collapse"
                                    data-target=".multi-collapse" aria-expanded="false"
                                    aria-controls="multiCollapseExample1 multiCollapseExample2">
                                <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>

                    <table class="table table-sm table-striped rep-data mt-3"
                           style="background-color: snow; font-size:16px; width:960px"
                           align=center>
                        <thead>
                        <tr class="text-left small" valign="top">
                            {{--                            <td class="text-center">Дата</td>--}}
                            <td class="text-left">Продукция</td>
                            <td class="text-center">ЕИ</td>
                            <td class="text-right">Произведено, ЕИ</td>
                            <td class="text-right">Произведено, м3</td>
                        </tr>

                        </thead>
                        <tbody>
                        <?php
                        $npp = 0;
                        $totQty_au = 0;
                        ?>
                        @foreach($recs as $rec)
                            <?php
                            $td_class = '';
                            ?>

                            {{--                            <tr class="text-left collapse show date_{{$tr_date}} multi-collapse">--}}
                            <tr class="text-left collapse show date_{{$tr_date??''}} multi-collapse">
                                <td class="text-left ">
                                    <a href="{{route('refitems.edit',$rec->refitmid)}}" target="_blank"
                                       class="text-decoration-none">{{$rec->name}}</a>
                                </td>
                                <td class="text-center small">{{$rec->unittype}}</td>
                                <td class="text-right">{{number_format($rec->qty,0)}}</td>
                                <td class="text-right">{{number_format($rec->qty_au,3)}}</td>
                            </tr>
                            <?php
                            $totQty_au += $rec->qty_au;
                            ?>
                        @endforeach

                        @if(1==1)

                            <tr class="text-left" style="background-color: #dacf64">
                                <td colspan="6" class="text-left pl-2"></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right">Всего:</td>
                                <td class="text-right font-weight-bold">{{number_format($totQty_au,3)}}</td>
                            </tr>
                        @endif
                        </tbody>
                        <tfoot>
                    </table>

                </div>
            @endif
        @endif

    </div>

    <script src="{{ asset('js/rep46.js') }}" defer></script>

@endsection
