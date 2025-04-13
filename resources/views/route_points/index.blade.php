<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Расценки маршрутов в баллах";
    $thisSysObjCode = 'route_points';

    $userid = \Auth::user()->id;
    ?>

    <link rel="stylesheet" href="/css/subnav.css">
    <form name="forIndex" id="forIndex" method="post" action="{{ route('route_points.index') }}">
        @csrf
        <div class="container">

            <?php
            $breadcrumbs = [
                'Справочники' => "/admin?tab=nsi-dic",
                $thisTitle => null,
            ];
            ?>
            @includeIf('layouts.breadcrumbs')

            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h3>{{$thisTitle}}</h3>

                    <div class="row mb-2">
                        <div class="offset-md-4 col-md-8 ">
                            <div class="subnav shift">
                                <ul>
                                    <li><a href="{{route('reports.rep71')}}" target="_blank"
                                           title="Сводка расходов/доходов авто">Отчет по баллам</a>
                                    </li>
                                    @if( \App\usrsysright::isUserHasRightByCode_cached($userid,'mchn_raids.read')
                                     and \Illuminate\Support\Facades\Route::has('orgstaff.index'))
                                        <li><a href="{{route('mchn_raids.index')}}"
                                               title="Учет перевозок">Перевозки</a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                        <div class="input-group col-md-4">
                        </div>
                    </div>

                    <div class="mt-2">

                        @include('layouts.edit_msgs')

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td class="small">#</td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'rp.src_placename','retroute'=>'route_points.index']) }}"
                                       class="btn "
                                       title="Сортировать">Начало маршрута {!! sort_mark('rp.src_placename',$sort_params) !!}</a>
                                </td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'rp.tgt_placename','retroute'=>'route_points.index']) }}"
                                       class="btn "
                                       title="Сортировать">Окончание маршрута {!! sort_mark('rp.tgt_placename',$sort_params) !!}</a>
                                </td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'rp.points','retroute'=>'route_points.index']) }}"
                                       class="btn " title="Сортировать">Баллы {!! sort_mark('rp.points',$sort_params) !!}</a>
                                </td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'rp.begdate','retroute'=>'route_points.index']) }}"
                                       class="btn "
                                       title="Сортировать">Период действия {!! sort_mark('rp.begdate',$sort_params) !!}</a>
                                </td>

                                <td style="text-align: center;">
                                    @if ($usrrights['create'])
                                        <a href="{{ route('route_points.create',0)}}"
                                           class="btn btn-warning btn-sm"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                    @if (1==0 and $usrrights['load']??false)
                                        <a href="{{ route($thisSysObjCode.'.load')}}"
                                           class="btn btn-success btn-sm"
                                           title="Загрузить записи о технике в формате файла XLS">
                                            <i class="fa fa-upload" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>

                            <tr style="text-align: center;">
                                <td></td>
                                <td class="row">
                                    <div class="input-group col-md-9">
                                        <input type="text" class="form-control c" name="s_src_placename"
                                               value="{{$search_params['s_src_placename'] ?? ''}}"
                                               placeholder="-начало-"/>
                                    </div>
                                </td>

                                <td>
                                    <div class="input-group col-md-9">
                                        <input type="text" class="form-control c" name="s_tgt_placename"
                                               value="{{ $search_params['s_tgt_placename'] ?? ''}}"
                                               placeholder="-окончание-"/>
                                    </div>
                                </td>
                                <td></td>
                                <td>
                                    <div class="input-group col-md-9">
                                    <?php
                                    $s_active_lst = ["" => "-все-", '1' => 'действует сейчас', '0' => 'архив'];
                                    ?>
                                    {!! Form::select('s_active', $s_active_lst, $search_params['s_active'] ?? '', ['class' => 'form-control']) !!}
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route('route_points.index') }}"
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
                            $cur_orgid = -1;
                            ?>
                            @foreach($recs as $item)
                                <?php
                                $colshift = (1 - $item->active) * 2;
                                $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                                ?>
                                <tr style="background-color: {{$tr_bg_col}}">
                                    <td class="small" style="text-align: right">
                                        {{$loop->index + $rec0}}
                                    </td>
                                    <td colspan="">
                                        <a name="{{$item->id}}"/>
                                        <a href="{{ route('route_points.edit',$item->id)}}" style="">
                                            {{$item->src_placename}}
                                        </a>
                                    </td>
                                    <td><a href="{{ route('route_points.edit',$item->id)}}" style="">{{$item->tgt_placename}}</a></td>
                                    <td class="font-weight-bold text-right">{{$item->points}}</td>
                                    <td class="small text-center">{{$item->begdate}} .. <span class="text-secondary"> {{$item->enddate}}</span></td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('route_points.edit',$item->id)}}"
                                           class="btn btn-sm btn-primary"
                                           title="Просмотреть/Изменить запись">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

        @includeIf('layouts.paginate_links')
    </form>
@endsection
