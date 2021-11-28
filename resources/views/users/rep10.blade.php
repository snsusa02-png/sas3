@extends('layouts.report')
@section('content')

    <?php
    $thisTitle = "Статистика операций пользователей в ИС";
    $thisSysObjId = 855;    //reports
    $thisObjId = 10;
    $action_url = route('reports.rep10');
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
                              action="{{ $action_url }}">
                            @csrf

                            <div class="row">
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
                        <h3>{{$thisTitle}}</h3>
                        @if(isset($s_begdate) and $s_begdate<>'')
                            с {{date_format(date_create($s_begdate),'d.m.Y')}}
                        @endif
                        @if(isset($s_enddate) and $s_enddate<>'')
                            по {{date_format(date_create($s_enddate),'d.m.Y')}}
                        @endif

                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>
                    </div>


                    <table class="rep-data mt-3" style="background-color: snow; font-size:16px; width:960px"
                           align=center>
                        <thead>
                        <tr class="text-left" valign="top">
                            <td class="small">#</td>
                            <td class="">Пользователь</td>
                            <td class="text-center">количество операций</td>
                            <td class="text-center">начальная операция</td>
                            <td class="text-center">конечная операция</td>
                            <td class="text-center">кол-во дней</td>
                            <td class="text-center">операций за день</td>

                        </tr>

                        </thead>
                        <tbody>
                        <?php
                        $npp = 0;
                        $curPeriod = '';
                        $curbuildobjid = '';
                        $curBuildObjName = '';
                        ?>
                        @foreach($recs as $rec)
                            <tr class="text-left">
                                <td class="small text-right">{{++$npp}}</td>
                                <td class="">{{$rec->username}}</td>
                                <td class="text-center">{{number_format($rec->cnt,0)}}</td>
                                <td class="text-center small">{{date_format(date_create($rec->mindate),'d.m.Y')}}</td>
                                <td class="text-center small">{{date_format(date_create($rec->maxdate),'d.m.Y')}}</td>
                                <td class="text-center">{{number_format($rec->daycnt,0)}}</td>
                                <td class="text-center">{{number_format($rec->cnt/$rec->daycnt,1)}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                    </table>

                </div>
    @endif
    @endif

@endsection
