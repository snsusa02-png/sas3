@extends('layouts.report')

<?php
$thisTitle = "-";
$thisSysObjId = 855;    //reports
$thisObjId = 47;
//$retURL = route('admin') . '#nsi-rep';
$retURL = route('paydocs.index');

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

                                    <div class="form-group col-md-6">
                                        <label for="s_ownorgid" class="required">от ГК:</label>
                                        {!! Form::select('s_ownorgid', $data->ownorgs, $search_params['s_ownorgid'],
                                                        [
                                                        'class' => 'form-control',
                                                        'placeholder' => '-',
                                                        'required' => 'required',
                                                        ])
                                                        !!}
                                    </div>

                                    <div class="form-group col-md-3">
                                        <label for="name">Категория:</label>
                                        {!! Form::select('s_showmode', $data->showmodes, $search_params['s_showmode'],
                                                        [
                                                        'class' => 'form-control',
                                                        'placeholder1' => '-все-',
                                                        ])
                                                        !!}
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="name">Куратор:</label>
                                        {!! Form::select('s_curatorid', $data->curators, $search_params['s_curatorid'],
                                                        [
                                                        'class' => 'form-control',
                                                        'placeholder' => '-все-',
                                                        ])
                                                        !!}
                                    </div>

                                    <?php
                                    $s_orgname = $search_params['s_orgname'] ?? '';
                                    $s_showmodename = $data->showmodes[$search_params['s_showmode']] ?? '';
                                    $s_curatorname = $data->curators[$search_params['s_curatorid']] ?? '';
                                    ?>

                                    @if(1==0)
                                        <div class="form-group col-md-3">
                                            <label for="name">Получатель:</label>
                                            {!! Form::select('s_orgid', $data->orgs, $search_params['s_orgid'],
                                                            [
                                                            'class' => 'form-control',
                                                            'placeholder' => '-все-',
                                                            ])
                                                            !!}
                                        </div>

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
                        @if(1==0)
                            <a class="btn btn-success btn-sm mr-3"
                               href="{{ route('reports.rep43_excel')  }}" title="Выгрузить результаты в Excel">
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
                        <b>{{$data->ownorgs[$search_params['s_ownorgid']]??''}}</b>
                        <div>{{$s_showmodename}}</div>
                        <div>{{$s_curatorname}}</div>
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
                           style="background-color: snow; font-size:16px; max-width:960px"
                           align=center>
                        <thead>
                        <tr class="text-left small" valign="top">
                            {{--                            <td class="text-center">Дата</td>--}}
                            <td class="text-left">Куратор</td>
                            <td class="text-left">Компания (физ. лицо)</td>
                            <td class="text-right">Сальдо, руб</td>
                        </tr>

                        </thead>
                        <tbody>
                        <?php
                        $npp = 0;
                        $totSum = 0;
                        $retURL = Request::url();
                        ?>

                        @foreach($recs as $rec)
                            <?php
                            $td_class = ($rec->org_saldo < 0) ? 'text-danger' : (($rec->org_saldo > 0) ? 'text-success' : '');
                            ?>
                            <tr class="text-left ">
                                <td class="small">{{$rec->org_curators??'-нет-'}}</td>
                                <td class="text-left small">
                                    <a href="{{route('reports.rep48',[$ownorgid,$rec->orgid])}}?returl={{$retURL}}"
                                       target="_blank1"
                                       class="text-decoration-none">{{$rec->orgname}}</a>
                                    <a href="{{route('reports.rep53',[$ownorgid,$rec->orgid])}}?returl={{$retURL}}"
                                       target="_blank1"
                                       class="ml-3 small text-decoration-none">по поставкам</a>
                                </td>
                                <td class="text-right {{$td_class}}">{{number_format($rec->org_saldo,2)}}

                            </tr>
                            <?php
                            $totSum += $rec->org_saldo;
                            ?>
                        @endforeach

                        @if(1==1)
                            <tr>
                                <td colspan="2" class="text-right">Всего:</td>
                                <td class="text-right font-weight-bold">{{number_format($totSum,2)}}</td>
                            </tr>
                        @endif
                        </tbody>
                        <tfoot>
                    </table>

                </div>
            @endif
        @endif

    </div>

    <script src="{{ asset('js/rep43.js') }}" defer></script>

@endsection
