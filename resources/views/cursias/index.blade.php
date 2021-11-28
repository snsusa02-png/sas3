@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Учет работы спецтехники и автомобилей";
    $thisSysObjCode = 'cursias';

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
                'Планирование' => "/planning",
                $thisTitle => null,
            ];
            ?>
            @includeIf('layouts.breadcrumbs')

            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-6">
                                <h3>{{$thisTitle}}</h3>
                            </div>
                            <div class="col-md-6">
                                <div class="subnav shift">
                                    <ul>
                                        <li><a href="{{route('reports.rep41')}}"
                                               title="Отчет по работе спецтехники и автомобилей за период">Отчет</a>
                                        </li>
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'machines.read'))
                                            <li><a href="{{route('machines.index')}}"
                                                   title="Спецтехника">Спецтехника</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'orgstaff.read'))
                                            <li><a href="{{route('orgstaff.index')}}"
                                                   title="Персонал организаций">Персонал</a></li>
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
                                <td rowspan="2">#</td>
                                <td rowspan="2">Дата</td>
                                <td rowspan="2">Техника</td>
                                <td rowspan="2">Работник</td>
                                <td colspan="2" class="text-center">Рабочих часов</td>
                                <td rowspan="2">Статус</td>

                                <td rowspan="2" class="text-center;">

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
                            <tr>
                                <td>работник</td>
                                <td>техника</td>
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
                                <td>
                                    <div class="input-group ">
                                        {!! Form::select('s_machineid', $data->machines
                                        , $search_params['s_machineid'],
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
                                <td></td>
                                <td></td>
                                <td>
                                    <div class="input-group ">
                                        {!! Form::select('s_statusid', $data->statuses??[]
                                        , $search_params['s_statusid']??'',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}

                                    </div>
                                </td>
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

                                    @if($item->wrkdate<>$cur_wrkdate)
                                        <tr style="background-color: #ccfcfb">
                                            <td colspan="7"><b>{{date_format(date_create($item->wrkdate),"d.m.Y")}}</b>
                                            </td>
                                            <td>
                                                @if ($usrrights['create'])
                                                    <a href="{{ route($thisSysObjCode.'.create')."?wrkdate={$item->wrkdate}"}}"
                                                       class="btn btn-warning btn-sm"
                                                       title="Добавить запись">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        <?php
                                        $cur_wrkdate = $item->wrkdate;
                                        $npp = 0;
                                        ?>
                                    @endif

                                    <tr style="background-color: {{$tr_bg_col}}">
                                        <td class="small text-right">
                                            {{++$npp}}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{route($thisSysObjCode.'.edit',$item->id)}}" name="{{$item->id}}"
                                               class="" style="color: black;"
                                               title="Просмотреть/Изменить запись">
                                                {{date_format(date_create($item->wrkdate),"d.m.Y") }}
                                            </a>
                                        </td>
                                        <td class="text-left small">
                                            {{$item->machine_name}}
                                        </td>
                                        <td class="text-center">
                                            {{$item->staff_name}}
                                        </td>
                                        <td class="text-center">
                                            {{$item->stfwrkhrs}}
                                        </td>
                                        <td class="text-center">
                                            {{$item->mchnwrkhrs}}
                                        </td>
                                        <td class="text-center" style="{{$status_style}}">
                                            {{$data->statuses[$item->statusid]??'?'}}
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
    </form>
@endsection

