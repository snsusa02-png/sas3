@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Учет запчастей";
    $thisSysObjCode = 'mchn_spare_usages';

    $statuses = [
        0 => 'черновик',
        1 => 'подготовлен',
    ];

    $userid = \Auth::user()->id;
    ?>

    <link rel="stylesheet" href="/css/subnav.css">
    <style>
        .searchby {
            background-color: #dbfbce;
            font-weight: bold;
        }
    </style>
    <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
        @csrf
        <div class="container">

            <?php
            $breadcrumbs = [
                'Данные' => "/rqsts",
                $thisTitle => null,
            ];
            ?>
            @includeIf('layouts.breadcrumbs')

            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-4">
                                <h3>{{$thisTitle}}</h3>
                            </div>
                            <div class="col-md-8">
                                <div class="subnav shift">
                                    <ul>
                                        <li><a href="{{route('reports.rep61')}}" target="_blank"
                                               title="Сводка расходов/доходов авто">Отчет по доходам/расходам</a>
                                        </li>
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'machines.read'))
                                            <li><a href="{{route('machines.index')}}"
                                                   title="Спецтехника">Спецтехника</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'paydocs.read'))
                                            <li><a href="{{route('fuelcard_pays.index')}}"
                                                   title="Учет перевозок">Заправки</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'mchn_raids.read'))
                                            <li><a href="{{route('mchn_raids.index')}}"
                                                   title="Учет перевозок">Рейсы</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'orgstaff.read'))
                                            <li><a href="{{route('orgstaff.index')}}"
                                                   title="Персонал">Персонал</a></li>
                                        @endif
                                        {{--                                        @if($usrrights['set_lockdate']??false)--}}
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,$thisSysObjCode.'.set_lockdate'))
                                            <li><a href="{{route('sysobj_lockdates.edit',1106)}}"
                                                   title="Установка даты блокировки данных"><i class="fa fa-lock "
                                                                                               aria-hidden="true"></i></a>
                                            </li>
                                        @endif
                                        @if(1==0 and $usrrights['finopers_refresh']??false)
                                            <li><a href="{{route('mchn_spare_usages.rfr_all_finopers')}}"
                                                   title="Пересчет фин. транзакций для всех документов">
                                                    <i class="fa fa-money fa-1" aria-hidden="true"></i>
                                                    <i class="fa fa-refresh fa-1"
                                                       aria-hidden="true"></i></a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2">
                        @include('layouts.edit_msgs')

                        {{--                        <div class="row">--}}
                        {{--                            <div class="form-group offset-md-6 col-md-2">--}}
                        {{--                                <label for="s_reguserid" class="required">Регистратор:</label>--}}
                        {{--                                {!! Form::select('s_reguserid', $data->regusers??[]--}}
                        {{--                                        , $search_params['s_reguserid']??'',--}}
                        {{--                                             [--}}
                        {{--                                             'class' => 'form-control',--}}
                        {{--                                             'placeholder' => '-все-',--}}
                        {{--                                             'onchange' => 'form.submit()',--}}
                        {{--                                             ]) !!}--}}
                        {{--                            </div>--}}
                        {{--                        </div>--}}

                        <table class="table table-striped table-bordered table-sm" style="background-color: snow;">
                            <thead>
                            <tr class="text-center">
                                <td>#</td>
                                <td>Дата</td>
                                <td>Авто</td>
                                <td>Запчасти</td>
                                <td>Сумма, &#8381;</td>
                                <td class="text-center;">

                                    @if (isset($data->template_id))
                                        <a href="{{ route($thisSysObjCode.'.create',0)}}"
                                           class="btn btn-warning btn-sm d-print-none"
                                           title="Добавить запись с данными из шаблона"
                                           onclick="return confirm('Добавить новую запись с данными из шаблона?')">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                        <a href="{{ route('user_templates.delete', $data->template_id)}}?returl={{Request::url()}}"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Удалить текущий шаблон?')"
                                           title="Удалить текущий шаблон"
                                        >
                                            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <a href="{{ route($thisSysObjCode.'.create',0)}}"
                                           class="btn btn-warning btn-sm d-print-none"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif

                                </td>
                            </tr>

                            <tr style="text-align: center;">
                                <td colspan="2">
                                    <div class="input-group">
                                        {!! Form::select('s_timestatuscode', $data->timestatuses??[], $search_params['s_timestatuscode']??'',
                                            [
                                            'class' => 'form-control small',
                                            'placeholder' => '-все-',
                                            'id' => 's_timestatuscode',
                                            'onchange' => 'form.submit()',
                                            ])
                                        !!}
                                    </div>
                                    <input type="date" class="form-control c" name="s_operdate"
                                           id="s_operdate"
                                           value="{{ $search_params['s_operdate'] ?? ''}}"
                                           placeholder="-название-"
                                           STYLE="display: none;"/>

                                </td>
                                <td>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" name="s_machine_name" list="machines"
                                                   class="form-control"
                                                   value="{{$search_params['s_machine_name']}}">
                                            <datalist id="machines">
                                                @foreach($data->machines as $key=>$val)
                                                    <option value="{{ $val }}">
                                                @endforeach
                                            </datalist>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                </td>
                                <td></td>
                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-success"
                                                formaction="{{ route($thisSysObjCode.'.index') }}"
                                                formmethod="post" title="Поиск">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $bgcols = array(
                                '#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC'
                            , '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7'
                            , '#aaccaa', '#bbccbb');

                            $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
                            $cur_operdate = -1;
                            $cur_id = -1;
                            $curDocID = "";
                            $npp = 0;
                            $curDate = date_format(date_create(), 'Y-m-d');
                            $cur_opertypeid = -1;
                            $userid = \Auth()->user()->id;
                            ?>
                            @foreach($recs as $item)
                                <?php

                                $colshift = (1 - $item->active) * 2;
                                $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                                if ($item->statusid == 0)
                                    $status_style = 'background-color:#fdc3c3';
                                elseif ($item->statusid == 2)
                                    $status_style = 'background-color:lightyellow';
                                elseif ($item->statusid == 4)
                                    $status_style = 'background-color:#d3f1d3';
                                else
                                    $status_style = '';

                                ?>

                                @if(isset($item->id))

                                    @if($item->operdate<>$cur_operdate)
                                        <tr style="background-color: #ccfcfb">
                                            <td colspan="5"><b>{{date_format(date_create($item->operdate),"d.m.Y")}}</b>
                                            </td>
                                            <td>
                                                @if ($usrrights['create'])
                                                    <a href="{{ route($thisSysObjCode.'.create')."?operdate={$item->operdate}"}}"
                                                       class="btn btn-warning btn-sm"
                                                       title="Добавить запись">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        <?php
                                        $cur_operdate = $item->operdate;
                                        $cur_id = -1;
                                        $cur_opertypeid = -1;
                                        $npp = 0;
                                        ?>
                                    @endif

                                    <tr style="background-color: {{$tr_bg_col}}">
                                        <td class="small text-right">
                                            {{++$npp}}
                                        </td>
                                        <td></td>
                                        <td class="text-left ">
                                            <a href="{{route($thisSysObjCode.'.edit',$item->id)}}" name="{{$item->id}}"
                                               class="text-decoration-none"
                                               title="Просмотреть/Изменить запись">
                                                {{$item->machine_name}}
                                            </a>
                                            <div class="float-right small">{{$item->notes}}</div>
                                        </td>
                                        <td>
                                            {{$item->spare_name}}
                                        </td>
                                        <td class="text-right">
                                            {{number_format($item->spare_sum,2)}}
                                        </td>

                                        <td class="text-right">
                                            <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}"
                                               class="btn btn-sm btn-primary"
                                               title="Просмотреть/Изменить запись">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endif

                            @endforeach
                            </tbody>
                        </table>

                        <div>
                            @includeIf('layouts.paginate_links')
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="{{ asset('js/mchn_spare_usages_index.js') }}" defer></script>

    </form>
@endsection

