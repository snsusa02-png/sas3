@extends('layouts.report')
@section('content')

    <?php
    $thisTitle = "Отчет о работе спецтехники и механизмов компании за период";
    $thisSysObjID = 855;    //reports
    $thisObjID = 1;
    $retURL = route('reports');
    ?>

    <style>
        .rep-data td {
            padding: 5px;
            border-collapse: collapse;
            border: 1px solid #e2e2e2;
        }

        .page {
            background-color: white;
        }

        .userSum {
            background-color: white;
            font-weight: bold;
            font-size: 1em;
        }

        .mnthSum {
            background-color: white;
            font-weight: bold;
            font-size: 1.0em;
        }

        .totSum {
            background-color: white;
            font-weight: bold;
            font-size: 1.1em;
        }

        .signers {
            width: 90%;
        }

        label.required:after {
            font-size: 75%;
            vertical-align: super;
            content: "*";
            color: red;
        }
    </style>
    <div class="container">

        <div class="row mb-3">
            <div class="col-md-9">

                <div class="params no-print card mt-3 d-print-none">
                    <div class="card-header font-weight-bold">
                        Параметры отчета "{{$thisTitle}}"
                    </div>
                    <div class="card-body">

                        <form name="forRep01" id="forRep01" method="post"
                              action="{{ route('reports.rep1') }}">
                            @csrf

                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="name" class="required">Владелец:</label>
                                    {!! Form::select('s_ownorgid', $ownorgs, $search_params['s_ownorgid']??''
                                                ,['placeholder' => '- укажите компанию-',
                                                'class' => 'form-control text-center',
                                                'required' => 'required',
                                                ]) !!}
                                </div>

                                <div class="form-group col-md-4">
                                    <label for="name" class="required">Арендатор:</label>
                                    {!! Form::select('s_orgid', $orgs, $search_params['s_orgid']??''
                                                ,['placeholder' => '- укажите компанию-',
                                                'class' => 'form-control text-center',
                                                'required' => 'required',
                                                ]) !!}
                                </div>

                                <div class="form-group col-md-4">
                                    <label for="name">Объект:</label>
                                    {!! Form::select('s_buildobjid', $buildobjs, $search_params['s_buildobjid']??''
                                                ,['placeholder' => '-',
                                                'class' => 'form-control text-center',
                                                ]) !!}
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="s_begdate" class="required">Начало периода:</label>
                                    <input type="date" class="form-control text-center"
                                           name="s_begdate"
                                           value="{{$search_params['s_begdate']??''}}"
                                           required/>
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="s_begdate" class="required">Окончание периода:</label>
                                    <input type="date" class="form-control text-center"
                                           name="s_enddate"
                                           value="{{$search_params['s_enddate']??''}}"
                                           required/>
                                </div>
                                {{--                                @dd($usrrights['set_paytype'])--}}
                                {{--                                @dd($paytypes, $usrrights['set_paytype'])--}}
                                @if( $usrrights['set_paytype'] )
                                    <div class="form-group col-md-3">
                                        <label for="name" class="">Тип оплаты:</label>
                                        {!! Form::select('s_paytypeid', $paytypes
                                        , $search_params['s_paytypeid']??''
                                                    ,['placeholder' => '- любой -',
                                                    'class' => 'form-control text-center',
                                                    ]) !!}
                                    </div>
                                @endif
                            </div>

                            <div style="border-top:1px solid silver;" class="mt-1 p-1">
                                <button type="submit" class="btn btn-sm btn-success"
                                        {{--								formaction="{{ route('mchnrqsts.index') }}"--}}
                                        formmethod="post">
                                    <i class="fa fa-refresh" aria-hidden="true"></i>
                                    Сформировать
                                </button>
                                <a class="btn btn-close btn-info btn-sm"
                                   href="{{ $retURL  }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                <span class="small" ml-2>
									<!-- <a href="{{route('objevntlog',['sysobjid'=>15, 'objid'=>82,'route'=>Route::current()->getName()])}}">журнал</a> -->
								</span>


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
                $ownorgid = $search_params['s_ownorgid'] ?? '';
                $s_begdate = $search_params['s_begdate'] ?? '';
                $s_enddate = $search_params['s_enddate'] ?? '';
                ?>


                <div class="page p-2">

                    <a class="btn btn-warning btn-sm print-window d-print-none float-right"
                       onclick="window.print();"
                       title="печать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>

                    <div class="owner-info">
                        <b>{{$ownorgname}}</b>
                        <br>Подразделение: <b>АТП</b>
                    </div>

                    <div class="font-weight-bold" align="center"
                         style="font-size: 18px;">
                        <h3>Отчет</h3>
                        о работе спецтехники и механизмов за период
                        с {{date_format(date_create($s_begdate),'d.m.Y')}}
                        по {{ date_format(date_create($s_enddate),'d.m.Y')}}

                        <br>предоставленной компании <b>{{$orgname}}</b>
                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>
                    </div>


                    <table class="rep-data mt-3" style="background-color: snow; font-size:16px; width:960px"
                           align=center>
                        <thead>
                        <tr class="text-center">
                            <td>Объект</td>
                            <td class="">Техника</td>
                            <td class="">Количество, час</td>
                            <td class="">Цена с НДС, руб</td>
                            <td class="">Итого с НДС, руб</td>
                        </tr>

                        </thead>
                        <tbody>
                        <?php
                        $npp = 0;
                        $totSum = 0;
                        ?>
                        @foreach($recs as $rec)
                            <?php
                            $lineSum = $rec->fcthrs * $rec->price;
                            $totSum += $lineSum;
                            $fctwrkperiod = date_format(date_create($rec->fctbegdt), 'd.m.Y H:i')
                                . ' - ' . date_format(date_create($rec->fctenddt), 'H:i');
                            ?>
                            <tr class="text-left" valign="top">
                                <td>
                                    {{ ($rec->rqsttypeid==2) ? $rec->tgt_addr: $rec->src_addr.' - '. $rec->tgt_addr}}
                                    <a href="{{route('mchnrqsts.edit',$rec->id)}}" target="_blank" class="d-print-none">...</a>
                                    <div class="ml-2 small float-right">
                                        {{$fctwrkperiod}}
                                    </div>
                                </td>
                                <td class="small">{{$rec->mchnname}}</td>
                                <td class="text-center">{{number_format($rec->fcthrs,0)}}</td>
                                <td class="text-right">{{number_format($rec->price,2)}}</td>
                                <td class="text-right">{{number_format($lineSum,2)}}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" class="text-right">
                                Итого:
                            </td>
                            <td class="text-right font-weight-bold">{{number_format($totSum,2)}}</td>
                        </tr>
                        </tbody>
                        <tfoot>
                        </tfoot>
                    </table>

                    <table class="signers mt-2" border=0 align="center">
                        <tr>
                            <td width="">Составил __________________ /Скляров Ю.А./</td>
                            <td width=""></td>
                            <td align="right">Начальник АТП __________________ /Пицан И.А./</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <table class="mt-2">
                                    <tr>
                                        <td>Отчет получил</td>
                                        <td> __________________</td>
                                        <td> __________________</td>
                                        <td> /__________________/</td>
                                    </tr>
                                    <tr style="font-size:8px;" align="center">
                                        <td></td>
                                        <td>Дата</td>
                                        <td>Подпись</td>
                                        <td>(отметка ббухгалтера о получении)</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
    @endif
    @endif

@endsection
