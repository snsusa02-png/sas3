@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Учет перевозок";
    $thisSysObjCode = 'mchn_raids';

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
        <div class="container-fluid">

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
                            <div class="col-md-2">
                                <h3>{{$thisTitle}}</h3>
                            </div>
                            <div class="col-md-10">
                                <div class="subnav shift">
                                    <ul>
                                        <li class=""><a href="{{route('reports.rep45')}}"
                                                        title="Анализ данных рейсов">Анализ</a>
                                        </li>
                                        <li><a href="{{route('reports.rep46')}}"
                                               title="Отчет за день">Отчет за день</a>
                                        </li>
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'paydocs.read'))
                                            <li><a href="{{route('paydocs.index')}}"
                                                   title="Платежи">Платежи</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'driver_works.read'))
                                            <li><a href="{{route('driver_works.index')}}"
                                                   title="Учет работы водителей">Учет работы</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'machines.read'))
                                            <li><a href="{{route('machines.index')}}"
                                                   title="Спецтехника">Спецтехника</a></li>
                                        @endif
                                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'orgs.read'))
                                            <li><a href="{{route('orgs.index')}}"
                                                   title="Заказчики">Клиенты</a></li>
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
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2">
                        @include('layouts.edit_msgs')

                        <table class="table table-striped table-bordered table-sm" style="background-color: snow;">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>Дата</td>
                                <td>Водитель, Авто</td>
                                <td>Диспетчер</td>

                                <td>Поставщик, Место загрузки</td>
                                <td>Груз, ЕИ</td>
                                <td>Объем, ЕИ</td>
                                <td>Цена, &#8381;</td>
                                <td>Сумма, &#8381;</td>
                                <td>Заказчик, Место выгрузки</td>
                                <td>Число рейсов</td>
                                <td>Оплата</td>

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
                                    {!! Form::select('s_driverid', $data->drivers
                                            , $search_params['s_driverid'],
                                                 [
                                                 'class' => 'form-control',
                                                 'placeholder' => '-все-',
                                                 'onchange' => 'form.submit()',
                                                 ]) !!}
                                        {!! Form::select('s_machineid', $data->machines
                                        , $search_params['s_machineid'],
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}

                                </td>
                                <td>
                                    {!! Form::select('s_disp_staffid', $data->dispatchers??[]
                                            , $search_params['s_disp_staffid']??'',
                                                 [
                                                 'class' => 'form-control',
                                                 'placeholder' => '-все-',
                                                 'onchange' => 'form.submit()',
                                                 ]) !!}
                                </td>

                                <td>
                                    {!! Form::select('s_suporgid', $data->suporgs??[]
, $search_params['s_suporgid']??'',
[
'class' => 'form-control',
'placeholder' => '-все-',
'onchange' => 'form.submit()',
]) !!}
                                    {!! Form::select('s_load_placeid', $data->load_places??[]
                                        , $search_params['s_load_placeid'],
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}
                                </td>
                                <td>
                                    <input type="text" name="s_ri_name" list="refitems" class="form-control"
                                           value="{{$search_params['s_ri_name']}}">
                                    <datalist id="refitems">
                                        @foreach($data->refitems as $key=>$val)
                                            <option value="{{ $val }}">
                                        @endforeach
                                    </datalist>
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>
                                    {!! Form::select('s_orgid', $data->orgs??[]
        , $search_params['s_orgid'],
             [
             'class' => 'form-control',
             'placeholder' => '-все-',
             'onchange' => 'form.submit()',
             ]) !!}

                                    {{--                                    {!! Form::select('s_unload_placeid', $data->unload_places??[]--}}
                                    {{--                                                                            , $search_params['s_unload_placeid'],--}}
                                    {{--                                                                                 [--}}
                                    {{--                                                                                 'class' => 'form-control',--}}
                                    {{--                                                                                 'placeholder' => '-все-',--}}
                                    {{--                                                                                 'onchange' => 'form.submit()',--}}
                                    {{--                                                                                 ]) !!}--}}
                                </td>
                                <td></td>
                                <td>
                                    <div class="input-group ">
                                        {!! Form::select('s_paytypeid', $data->paytypes??[]
                                        , $search_params['s_paytypeid']??'',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}
                                    </div>
                                </td>
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
                            $cur_wrkdate = -1;
                            $cur_id = -1;
                            $curDocID = "";
                            $npp = 0;
                            $curDate = date_format(date_create(), 'Y-m-d');
                            $cur_opertypeid = -1;
                            $userid = \Auth()->user()->id;
                            $saledirs = \App\mr_oper::saledirs();
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
                                            <td colspan="12"><b>{{date_format(date_create($item->wrkdate),"d.m.Y")}}</b>
                                            </td>
                                            <td>
                                                @if ($usrrights['create'])
                                                    <a href="{{ route($thisSysObjCode.'.create',0)."?wrkdate={$item->wrkdate}"}}"
                                                       class="btn btn-warning btn-sm"
                                                       title="Добавить запись">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        <?php
                                        $cur_wrkdate = $item->wrkdate;
                                        $cur_id = -1;
                                        $cur_opertypeid = -1;
                                        $npp = 0;
                                        ?>
                                    @endif
                                    @if($item->opertypeid<>$cur_opertypeid)
                                        <tr class="bg-warning">
                                            <td></td>
                                            <td colspan="12"
                                                class="font-weight-bold font-italic">{{$item->opertype_name}}</td>
                                        </tr>
                                        <?php
                                        $cur_opertypeid = $item->opertypeid;
                                        ?>
                                    @endif
                                    @if($item->id<>$cur_id)
                                        @if($cur_id<>-1)
                                            <tr style="background-color: #e0ffd5;height: 1px;">
                                                <td colspan="14" style="height: 0px;"/>
                                            </tr>
                                        @endif
                                        <?php
                                        $cur_id = $item->id;
                                        ?>
                                    @endif

                                    <tr style="background-color: {{$tr_bg_col}}">
                                        <td class="small text-right">
                                            {{++$npp}}
                                        </td>
                                        <td class="text-center">
                                            {{$saledirs[$item->sale_dir]??'?'}}
                                        </td>
                                        <td class="text-left ">
                                            <a href="{{route($thisSysObjCode.'.edit',$item->id)}}" name="{{$item->id}}"
                                               class="text-decoration-none"
                                               title="Просмотреть/Изменить запись">
                                                {{$item->staff_name}}

                                                <div class="mt-1 ml-3 small">{{$item->machine_name}}</div>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            {{$item->disp_name}}
                                        </td>
                                        <td class="text-center">
                                            {{$item->suporg_name}}
                                            <div class="mt-1 ml-3 small">{{$item->sup_placename}}</div>
                                        </td>
                                        <td class="text-center ">
                                            {{$item->refitem_name}}, {{$item->refitem_unit}}
                                        </td>
                                        <td class="text-right">
                                            {{$item->itm_qty}}
                                        </td>
                                        <td class="text-right">
                                            {{number_format($item->itm_price,2)}}
                                        </td>
                                        <td class="text-right">
                                            {{number_format($item->itm_qty*$item->itm_price,2)}}
                                        </td>
                                        <td class="text-center">
                                            {{$item->org_name}}
                                            <div class="mt-1 ml-3 small">{{$item->org_placename}}</div>
                                        </td>
                                        <td class="text-right">
                                            {{$item->raid_qty}}
                                        </td>
                                        <td class="text-center">
                                            {{$data->paytypes[$item->paytypeid]??'?'}}
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
        <script src="{{ asset('js/mchn_raid_index.js') }}" defer></script>

    </form>
@endsection

