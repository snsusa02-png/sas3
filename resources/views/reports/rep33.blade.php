@extends('layouts.report')
@section('content')
    <?php
    $report_id = 33;
    $thisTitle = $data->report->name ?? "Товарный запас";

    //$thisSysObjCode = 'invoices';
    $thisSysObjId = 855;    //reports
    $thisObjId = $report_id;
    $action_url = route('reports.rep' . $thisObjId);
    //$retURL = route('reports');
    $retURL = \Request::get('returl') ?? $data->retURL ?? route('reports');

    ?>


    <link rel="stylesheet" href="/css/tags.css">

    <div class="container">

        <div class="row mb-3">
            <div class="col-md-12">

                @includeIf('layouts.edit_msgs')

                <div class="params no-print card mt-3 d-print-none">
                    <div class="card-header font-weight-bold">
                        Параметры отчета "{{$thisTitle}}"

                        <span class="float-right">
                        <a class="btn btn-close btn-light btn-sm"
                           style="float:right;"
                           href="{{ $retURL }}"
                           title="Вернуться в список">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </a>
                            </span>
                    </div>
                    <div class="card-body">

                        <form name="forRep01" id="forRep01" method="post"
                              action="{{ $action_url }}">
                            @csrf

                            @if(1==1)
                                <div class="row">

                                    <div class="form-group col-md-3">
                                        <label for="s_begdate">Склад:</label>
                                        {!! Form::select('s_wrhid', $data->wrhs??[], $search_params['s_wrhid'] ?? '',
                                             [
                                             'class' => 'form-control small',
                                             'placeholder' => '-выбор-',
                                             ]) !!}
                                    </div>

                                    <div class="form-group col-md-3">
                                        <label for="s_ownorgid">Владелец:</label>
                                        {!! Form::select('s_ownorgid', $data->ownorgs??[], $search_params['s_ownorgid'] ?? '',
                                             [
                                             'class' => 'form-control small',
                                             'placeholder' => '-любой-',
                                             ]) !!}
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="s_ownorgid">Категория:</label>
                                        {!! Form::select('s_itmtypeid', $data->itmtypes??[], $search_params['s_itmtypeid'] ?? '',
                                             [
                                             'class' => 'form-control small',
                                             'placeholder' => '-любая-',
                                             ]) !!}
                                    </div>

                                    <div class="form-group col-md-3">
                                        <label for="s_itmname" class="">Товар:</label>
                                        <input type="text" class="form-control text-center"
                                               name="s_itmname"
                                               value="{{$search_params['s_itmname']??''}}"
                                        />
                                    </div>

                                    @if(1==0)
                                        <div class="form-group col-md-3">
                                            <label for="s_begdate">Окончание периода:</label>
                                            <input type="date" class="form-control text-center"
                                                   name="s_enddate"
                                                   value="{{$search_params['s_enddate']??''}}"
                                            />
                                        </div>

                                        <div class="form-group col-md-3">
                                            <label for="s_begdate">Поставщик:</label>
                                            {!! Form::select('s_orgid', $data->orgs, $search_params['s_orgid'] ?? '',
                                                 [
                                                 'class' => 'form-control small',
                                                 'placeholder' => '-любой-',
                                                 ]) !!}
                                        </div>
                                    @endif

                                </div>
                            @endif

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
            @if (count($recs)==0)

                <div class="page p-3 d-print-none" align="center" style="background-color: white">
                    нет операций для заданных значений
                </div>

            @else
                <?php
                $s_wrhid = $search_params['s_wrhid'] ?? '';
                $s_min_regnum = $search_params['s_min_regnum'] ?? '';
                $s_max_regnum = $search_params['s_max_regnum'] ?? '';
                $s_begdate = $search_params['s_begdate'] ?? '';
                $s_enddate = $search_params['s_enddate'] ?? '';

                ?>


                <div class="page p-2 " style="background-color: white">

                    <a class="btn btn-warning btn-sm print-window d-print-none float-right"
                       onclick="window.print();"
                       title="печать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>

                    <div class="mt-2" align="center"
                         style="font-size: 18px;">
                        <h3>{{$thisTitle}}</h3>
                        @if(isset($data->conditions) and $data->conditions<>'')
                            {!! $data->conditions !!}
                        @endif
                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>
                    </div>


                    <style>
                        tr:nth-child(even) .aux {
                            background: snow
                        }

                        tr:nth-child(odd) .aux {
                            background: #dfdfdf
                        }
                    </style>
                    <table class="table table-sm table-striped rep-data mt-3"
                           style="background-color: snow; font-size:16px; width:960px"
                           align=center>

                        <thead>
                        <tr>
                            <td class="small">#</td>
                            <td>Склад, Отделение, Владелец, Наименование материала/товара</td>
                            <td class="text-right">Наличие, ЕИ</td>
                            <td class="text-center">ЕИ</td>
                            {{--                            <td class="text-right">Вес, кг</td>--}}
                            <td class="text-right">Цена, руб</td>
                            <td class="text-right">Сумма, руб</td>
                        </tr>
                        </thead>

                        <tbody>
                        <?php
                        $bgcols = array(
                            '#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC'
                        , '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7'
                        , '#aaccaa', '#bbccbb');

                        $rec0 = 1;
                        $totDocSum = 0;
                        $ownorgSum = 0;
                        $curOwnMark = "";
                        $curWrhID = -1;
                        $curBoxID = -1;
                        $curOwnOrgID = -1;
                        $curItmTypeID = -1;
                        ?>
                        @foreach($recs as $itm)
                            <?php
                            //$colshift = (1 - $itm->active) * 2;
                            $colshift = (1 - 1) * 2;
                            $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                            $colshift = (isset($itm->enddate) and $itm->enddate < now()) ? 2 : 0;
                            $period_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                            //                            $tclass = ($itm->categoryid == 1) ? 'badge-success'
                            //                                : (($itm->categoryid == 2) ? 'badge-danger' : 'badge-warning');
                            ?>
                            @if($itm->wrhid<>$curWrhID
                                or $itm->boxid<>$curBoxID
                                or $itm->ownorgid<>$curOwnOrgID
                                or $itm->itmtypeid<>$curItmTypeID)
                                <tr>
                                    <td colspan="7"></td>
                                </tr>
                            @endif
                            @if($itm->wrhid<>$curWrhID)
                                @if(1==0 and $curWrhID<>-1)
                                    <tr>
                                        <td colspan="4" class="text-right">Итого:</td>
                                        <td class="text-right font-weight-bold">
                                            {{number_format($wrhSum,2)}}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                                <tr style="background-color: #fdffd1">
                                    <td></td>
                                    <td colspan="6" class="font-italic">
                                        склад: <b>{{$itm->wrh_name}}</b>
                                    </td>
                                </tr>
                                <?php
                                $curWrhID = $itm->wrhid;
                                $wrhSum = 0;
                                $curBoxID = -1;
                                ?>
                            @endif

                            @if($itm->boxid<>$curBoxID)
                                @if(1==0 and $curBoxID<>-1)
                                    <tr>
                                        <td colspan="4" class="text-right">Итого:</td>
                                        <td class="text-right font-weight-bold">
                                            {{number_format($boxSum,2)}}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                                <tr style="background-color: #c2f2f0">
                                    <td></td>
                                    <td colspan="6" class="font-italic pl-2"
                                    >отделение: <b>{{$itm->box_name}}</b>
                                    </td>
                                </tr>
                                <?php
                                $curBoxID = $itm->boxid;
                                $boxSum = 0;
                                $curOwnOrgID = -1;
                                ?>
                            @endif
                            @if($itm->ownorgid<>$curOwnOrgID)
                                @if(1==1 and $curOwnOrgID<>-1)
                                    <tr>
                                        <td colspan="5" class="text-right">Итого по владельцу:</td>
                                        <td class="text-right font-weight-bold">
                                            {{number_format($ownorgSum,2)}}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif

                                <tr style="background-color: #efffce">
                                    <td></td>
                                    <td colspan="6" class="font-italic pl-3"
                                    >владелец: <b>{{$itm->ownorg_name}}</b>
                                    </td>
                                </tr>
                                <?php
                                $curOwnOrgID = $itm->ownorgid;
                                $ownorgSum = 0;
                                $curItmTypeID = -2;
                                ?>
                            @endif
                            @if($itm->itmtypeid<>$curItmTypeID)
                                @if(1==0 and $curItmTypeID<>-1)
                                    <tr>
                                        <td colspan="5" class="text-right">Итого по категории:</td>
                                        <td class="text-right font-weight-bold">
                                            {{number_format($ItmTypeSum,2)}}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                                <tr style="background-color: #ffebce">
                                    <td></td>
                                    <td colspan="6" class="font-italic pl-4"
                                    >категория: <b>{{$itm->itmtype_name}}</b>
                                    </td>
                                </tr>
                                <?php
                                $curItmTypeID = $itm->itmtypeid;
                                $ItmTypeSum = 0;
                                ?>
                            @endif

                            <tr style="background-color: {{$tr_bg_col}}">
                                <td class="small text-right">
                                    {{$loop->index + $rec0}} <a name="{{$itm->id}}"></a>
                                </td>

                                <td class="small text-left">
                                    <a href="{{route('refitems.edit',$itm->refitmid)}}" target="_blank">
                                        <b>{{$itm->ri_name}}</b>
                                    </a>
                                </td>
                                <td class="text-right small">
                                    {{number_format($itm->qty,$itm->decimal_dgts)}}
                                </td>
                                <td class="text-center small">
                                    {{$itm->unittype_name}}
                                </td>
                                {{--                                <td class="text-right small">--}}
                                {{--                                    {{number_format($itm->qty*$itm->grossweight,1)}}--}}
                                {{--                                </td>--}}
                                <td class="text-right small">
                                    {{number_format($itm->price,2)}}
                                </td>
                                <td class="text-right small">
                                    {{number_format($itm->qty*$itm->price,2)}}
                                </td>
                                <td>
                                </td>
                            </tr>
                            <?php
                            $itmSum = $itm->qty * $itm->price;
                            $totDocSum += $itmSum;
                            $ownorgSum += $itmSum;
                            $ItmTypeSum += $itmSum;
                            ?>
                        @endforeach

                        @if(1==1 and count($recs)>0)
                            @if(1==0 and $curItmTypeID<>-1)
                                <tr>
                                    <td colspan="5" class="text-right">Итого по категории:</td>
                                    <td class="text-right font-weight-bold">
                                        {{number_format($ItmTypeSum,2)}}
                                    </td>
                                    <td></td>
                                </tr>
                            @endif
                            @if(1==1 and $curOwnOrgID<>-1)
                                <tr>
                                    <td colspan="5" class="text-right">Итого по владельцу:</td>
                                    <td class="text-right font-weight-bold">
                                        {{number_format($ownorgSum,2)}}
                                    </td>
                                    <td></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="5" class="text-right">Всего:</td>
                                <td class="text-right font-weight-bold">
                                    {{number_format($totDocSum,2)}}
                                </td>
                                <td></td>
                            </tr>
                        @endif
                        </tbody>
                        <tfoot>
                    </table>

                    <script type="text/javascript">

                        // window.document.onload = window.print();

                        window.onafterprint = function () {
                            setTimeout(function () {
                                window.close();
                            }, 500);
                        }

                        window.onfocus = function () {
                            setTimeout(function () {
                                window.close();
                            }, 500);
                        }
                    </script>


                </div>
            @endif
        @endif


        <script src="{{ asset('js/rep23.js') }}" defer></script>

    </div>

@endsection
