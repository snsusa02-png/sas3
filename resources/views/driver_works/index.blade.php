@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Учет работы водителей";
    $thisSysObjCode = 'driver_works';

    $statuses = [
        0 => 'черновик',
        1 => 'подготовлен',
    ];

    $userid = \Auth::user()->id;
    ?>

    <link rel="stylesheet" href="/css/subnav.css">
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
                                        <li><a href="{{route('reports.rep51')}}"
                                               title="Табель">Табель</a></li>

                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'mchn_raids.read'))
                                            <li><a href="{{route('mchn_raids.index')}}"
                                                   title="Рейсы">Рейсы</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'machines.read'))
                                            <li><a href="{{route('machines.index')}}"
                                                   title="Спецтехника">Спецтехника</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'orgstaff.read'))
                                            <li><a href="{{route('orgstaff.index')}}"
                                                   title="Персонал организаций">Персонал</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'refitems.read'))
                                            <li><a href="{{route('refitems.index')}}"
                                                   title="Товарная номенклатура">Товары</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,$thisSysObjCode.'.set_lockdate'))
                                            <li><a href="{{route('sysobj_lockdates.edit',1141)}}"
                                                   title="Установка даты блокировки данных"><i class="fa fa-lock "
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

                        <table class="table table-striped table-bordered " style="background-color: snow;">
                            <thead>
                            <tr>
                                <td rowspan="1">#</td>
                                <td rowspan="1">Дата</td>
                                <td rowspan="1">Работник</td>
                                <td rowspan="1">Техника</td>
                                <td colspan="1" class="text-center">Рейсы, руб</td>
                                <td colspan="1" class="text-center">Простой, руб</td>
{{--                                <td colspan="1" class="text-center">Ремонт, руб</td>--}}
                                <td rowspan="1" class="text-center">Всего, руб</td>

                                <td rowspan="1" class="text-center;">

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
                                <td></td>
                                <td>
                                    <div class="input-group ">
                                        {!! Form::select('s_date', $data->dates??[]
                                        , $search_params['s_date']??'',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}

                                    </div>
                                </td>
                                <td>{!! Form::select('s_staffid', $data->staffs
                                        , $search_params['s_staffid'],
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}
                                </td>
                                <td>
                                    {!! Form::select('s_machineid', $data->machines
                                    , $search_params['s_machineid'],
                                         [
                                         'class' => 'form-control',
                                         'placeholder' => '-все-',
                                         'onchange' => 'form.submit()',
                                         ]) !!}
                                </td>
                                <td></td>
                                <td></td>
{{--                                <td></td>--}}
                                <td></td>
                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
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
                            $cur_wrkdate = -1;
                            $curDocID = "";
                            $npp = 0;
                            $curDate = date_format(date_create(), 'Y-m-d');
                            $userid = \Auth()->user()->id;
                            ?>
                            @foreach($recs as $rec)
                                <?php

                                $colshift = (1 - $rec->active) * 2;
                                $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                                $rec->statusid = 0;
                                if ($rec->statusid == 0)
                                    $status_style = 'background-color:#fdc3c3';
                                elseif ($rec->statusid == 2)
                                    $status_style = 'background-color:lightyellow';
                                elseif ($rec->statusid == 4)
                                    $status_style = 'background-color:#d3f1d3';
                                else
                                    $status_style = '';

                                ?>

                                @if(isset($rec->id))

                                    @if($rec->wrkdate<>$cur_wrkdate)
                                        <tr style="background-color: #ccfcfb">
                                            <td colspan="9"><b>{{date_format(date_create($rec->wrkdate),"d.m.Y")}}</b>
                                                @if ($usrrights['create'])
                                                    <a href="{{ route($thisSysObjCode.'.create')."?wrkdate={$rec->wrkdate}"}}"
                                                       class="btn btn-warning btn-sm ml-3"
                                                       title="Добавить запись">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        <?php
                                        $cur_wrkdate = $rec->wrkdate;
                                        $npp = 0;
                                        ?>
                                    @endif

                                    <tr style="background-color: {{$tr_bg_col}}">
                                        <td class="small text-right">
                                            {{++$npp}}
                                        </td>
                                        <td class="text-center">
                                            {{--                                                {{date_format(date_create($rec->wrkdate),"d.m.Y") }}--}}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{route($thisSysObjCode.'.edit',$rec->id)}}" name="{{$rec->id}}"
                                               class="" style="color: black;"
                                               title="Просмотреть/Изменить запись">
                                                {{$rec->staff_name}}
                                            </a>
                                        </td>
                                        <td class="text-left small">
                                            {{$rec->machine_name}}
                                            <div class="float-right">{{$rec->wrktype_name}}</div>
                                        </td>
                                        <td class="text-center">
                                            {{$rec->hrs_salary + $rec->raid_sum}}
                                        </td>
                                        <td class="text-center">
                                            {{$rec->breaks_sum}}
                                        </td>
{{--                                        <td class="text-center">--}}
{{--                                            {{$rec->repair_sum}}--}}
{{--                                        </td>--}}
                                        <td class="text-center">
                                            {{$rec->salary_sum}}
                                        </td>

                                        <td class="text-right">
                                            <a href="{{ route($thisSysObjCode.'.edit',$rec->id)}}"
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
    </form>
@endsection

