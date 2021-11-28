@extends('layouts.report')
@section('content')

    <?php
    $thisTitle = "Сводный отчет о работе спецтехники и механизмов компании за период";
    $thisSysIbjId = 855;    //reports
    $thisObjId = 3;
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
                              action="{{ route('reports.rep3') }}">
                            @csrf

                            <div class="row">
                                <div class="form-group col-md-2">
                                    <label for="s_begdate">Начало периода<sup style="color:red;">*</sup>:</label>
                                    <input type="date" class="form-control text-center"
                                           name="s_begdate"
                                           value="{{$search_params['s_begdate']??''}}"
                                           required/>
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="s_begdate">Окончание периода<sup style="color:red;">*</sup>:</label>
                                    <input type="date" class="form-control text-center"
                                           name="s_enddate"
                                           value="{{$search_params['s_enddate']??''}}"
                                           required/>
                                </div>

                                <div class="form-group col-md-4">
                                    <label for="name">Владелец:</label>
                                    {!! Form::select('s_ownorgid', $ownorgs, $search_params['s_ownorgid']??''
                                                ,['placeholder' => '- все -',
                                                'class' => 'form-control text-center',
                                                ]) !!}
                                </div>

                                @if( $usrrights['set_paytype']??false )
                                    <div class="form-group col-md-3">
                                        <label for="name" class="">Тип оплаты:</label>
                                        {!! Form::select('s_paytypeid', $data->paytypes
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
                                @if(1==1)
                                    <span class="small float-right" ml-2>
									 <a href="{{route('objevntlog',['sysobjid'=>$thisSysIbjId, 'objid'=>$thisObjId,'route'=>Route::current()->getName()])}}">журнал</a>
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
                $ownorgid = $search_params['s_ownorgid'] ?? '';
                $s_begdate = $search_params['s_begdate'] ?? '';
                $s_enddate = $search_params['s_enddate'] ?? '';


                $_monthsList = array(
                    ".01." => "январь",
                    ".02." => "февраль",
                    ".03." => "март",
                    ".04." => "апрель",
                    ".05." => "май",
                    ".06." => "июнь",
                    ".07." => "июль",
                    ".08." => "август",
                    ".09." => "сентябрь",
                    ".10." => "октябрь",
                    ".11." => "ноябрь",
                    ".12." => "декабрь"
                );

                ?>


                <div class="page p-2 ">

                    <a class="btn btn-warning btn-sm print-window d-print-none float-right"
                       onclick="window.print();"
                       title="печать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>

                    <div class="font-weight-bold mt-2" align="center"
                         style="font-size: 18px;">
                        <h3>Сводный отчет</h3>
                        о работе спецтехники и механизмов за период
                        с {{date_format(date_create($s_begdate),'d.m.Y')}}
                        по {{ date_format(date_create($s_enddate),'d.m.Y')}}

                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>
                    </div>


                    <table class="rep-data mt-3" style="background-color: snow; font-size:16px; width:960px"
                           align=center>
                        <thead>
                        <tr class="text-left" valign="top">
                            <td class="">Владелец</td>
                            <td class="">Период</td>
                            <td class="">Арендатор</td>
                            <td class="text-right">Кол-во заявок</td>
                            <td class="text-right">Общее время заявок, час</td>
                            <td class="text-center">Среднее время, час</td>
                            <td class="text-right">Сумма, руб</td>
                            <td class="text-right">Топливо, л</td>

                        </tr>

                        </thead>
                        <tbody>
                        <?php
                        $npp = 0;
                        $totSum = 0;
                        $totCnt = 0;
                        $totQtySum = 0;
                        $totFuelQty = 0;

                        $carorgSum = 0;
                        $carorgCnt = 0;
                        $periodSum = 0;
                        $periodCnt = 0;
                        $periodQtySum = 0;
                        $curPeriod = '';
                        $curCarOrgID = '';
                        $curCarOrgName = '';
                        $carorgQtySum = 0;
                        ?>
                        @foreach($recs as $rec)
                            @if($rec->carorgid<>$curCarOrgID)
                                @if($curPeriod<>'')
                                    <tr style="background-color: #f2f1e0">
                                        <td colspan="3" class="text-right">
                                            Итого за период:
                                        </td>
                                        <td class="text-right font-weight-bold">{{number_format($periodCnt,0)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format($periodQtySum,1)}}</td>
                                        <td class="text-center small font-weight-bold">{{number_format($periodQtySum/$periodCnt,1)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format($periodSum,2)}}</td>
                                    </tr>
                                    <?php
                                    $periodSum = 0;
                                    $periodCnt = 0;
                                    $periodQtySum = 0;
                                    ?>
                                @endif
                                @if($curCarOrgID<>'')
                                    <tr style="background-color: #daf4ff">
                                        <td colspan="3" class="text-right">
                                            Итого владельцу ({{$curCarOrgName}}):
                                        </td>
                                        <td class="text-right font-weight-bold">{{number_format($carorgCnt,0)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format($carorgQtySum,1)}}</td>
                                        <td class="text-center small font-weight-bold">{{number_format($carorgQtySum/$carorgCnt,1)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format($carorgSum,2)}}</td>
                                    </tr>
                                    <?php
                                    ?>
                                @endif
                                <tr style="background-color: #cfe9f4">
                                    <td colspan="7">&nbsp;<b>{{$rec->carorgname}}</b></td>
                                </tr>
                                <?php
                                $curCarOrgID = $rec->carorgid;
                                $curCarOrgName = $rec->carorgname;
                                $curPeriod = '';
                                $carorgSum = 0;
                                $carorgCnt = 0;
                                $carorgQtySum = 0;
                                ?>
                            @endif

                            @if($rec->perbegdate<>$curPeriod)
                                @if($curPeriod<>'')
                                    <tr style="background-color: #f2f1e0">
                                        <td colspan="3" class="text-right">
                                            Итого за период:
                                        </td>
                                        <td class="text-right font-weight-bold">{{number_format($periodCnt,0)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format($periodQtySum,1)}}</td>
                                        <td class="text-center small font-weight-bold">{{number_format($periodQtySum/$periodCnt,1)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format($periodSum,2)}}</td>
                                    </tr>
                                    <?php
                                    $periodSum = 0;
                                    $periodCnt = 0;
                                    $periodQtySum = 0;
                                    ?>
                                @endif

                                <?php
                                //заменяем число месяца на название:
                                $_mD = date(".m.", strtotime($rec->perbegdate)); //для замены
                                //$currentDate = str_replace($_mD, " ".$_monthsList[$_mD]." ", $rec->perbegdate);

                                ?>
                                <tr>
                                    <td/>
                                    <td colspan="4">&nbsp;<b>{{date_format(date_create($rec->perbegdate),"Y") }},
                                            {{$_monthsList[$_mD]}}</b></td>
                                </tr>
                                <?php
                                $curPeriod = $rec->perbegdate;
                                $periodSum = 0;
                                ?>
                            @endif
                            <tr class="text-left">
                                <td class=""></td>
                                <td class=""></td>
                                <td class="">{{$rec->initorgname}}</td>
                                <td class="text-right">{{number_format($rec->rqst_cnt,0)}}</td>
                                <td class="text-right">{{number_format($rec->qty_sum,1)}}</td>
                                <td class="text-center small">{{number_format($rec->qty_avg,1)}}</td>
                                <td class="text-right">{{number_format($rec->fct_sum,2)}}</td>
                                <td class="text-right">{{number_format($rec->fuel_qty,1)}}</td>
                            </tr>
                            <?php
                            $totSum += $rec->fct_sum;
                            $totCnt += $rec->rqst_cnt;
                            $totQtySum += $rec->qty_sum;
                            $periodSum += $rec->fct_sum;
                            $periodCnt += $rec->rqst_cnt;
                            $periodQtySum += $rec->qty_sum;
                            $carorgSum += $rec->fct_sum;
                            $carorgCnt += $rec->rqst_cnt;
                            $carorgQtySum += $rec->qty_sum;

                            $totFuelQty += $rec->fuel_qty;
                            ?>
                        @endforeach
                        @if($curPeriod<>'')
                            <tr style="background-color: #f2f1e0">
                                <td colspan="3" class="text-right">
                                    Итого за период:
                                </td>
                                <td class="text-right font-weight-bold">{{number_format($periodCnt,0)}}</td>
                                <td class="text-right font-weight-bold">{{number_format($periodQtySum,1)}}</td>
                                <td class="text-center small font-weight-bold">{{number_format($periodQtySum/$periodCnt,1)}}</td>
                                <td class="text-right font-weight-bold">{{number_format($periodSum,2)}}</td>
                            </tr>
                        @endif
                        @if($curCarOrgID<>'')
                            <tr style="background-color: #daf4ff">
                                <td colspan="3" class="text-right">
                                    Итого владельцу ({{$curCarOrgName}}):
                                </td>
                                <td class="text-right font-weight-bold">{{number_format($carorgCnt,0)}}</td>
                                <td class="text-right font-weight-bold">{{number_format($carorgQtySum,1)}}</td>
                                <td/>
                                <td class="text-right font-weight-bold">{{number_format($carorgSum,2)}}</td>
                            </tr>
                        @endif
                        </tbody>
                        <tfoot>
                        <tr style="background-color: #d4eda8">
                            <td colspan="3" class="text-right">
                                Всего:
                            </td>
                            <td class="text-right font-weight-bold">{{number_format($totCnt,0)}}</td>
                            <td class="text-right font-weight-bold">{{number_format($totQtySum,1)}}</td>
                            <td/>
                            <td class="text-right font-weight-bold">{{number_format($totSum,2)}}</td>
                            <td class="text-right font-weight-bold">{{number_format($totFuelQty,1)}}</td>
                        </tr>
                        </tfoot>
                    </table>

                </div>
    @endif
    @endif

@endsection
