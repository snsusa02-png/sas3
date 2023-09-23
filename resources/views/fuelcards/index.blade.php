<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Топливные карты";
    $thisSysObjCode = 'fuelcards';

    $userid = \Auth::user()->id;
    ?>

    <link rel="stylesheet" href="/css/subnav.css">
    <form name="forIndex" id="forIndex" method="post" action="{{ route('fuelcards.index') }}">
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
                                    @if(1==1 and \Illuminate\Support\Facades\Route::has('fuelcard_pays.index'))
                                        <li><a href="{{route('fuelcard_pays.index')}}"
                                               title="Контрагенты">Заправки</a>
                                        </li>
                                    @endif
                                    <li><a href="{{route('reports.rep61')}}" target="_blank"
                                           title="Сводка расходов/доходов авто">Отчет по доходам/расходам</a>
                                    </li>
                                    @if(1==1 and \Illuminate\Support\Facades\Route::has('orgs.index'))
                                        <li><a href="{{route('orgs.index')}}"
                                               title="Контрагенты">Контрагенты</a>
                                        </li>
                                    @endif
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
                                <td>Владелец</td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'fc.num','retroute'=>'fuelcards.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">№ и название {!! sort_mark('fc.num',$sort_params) !!}</a>
                                </td>

                                <td style="text-align: center;">
                                    @if ($usrrights['create'])
                                        <a href="{{ route('fuelcards.create',0)}}"
                                           class="btn btn-warning btn-sm"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                    @if ($usrrights['load']??false)
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
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_orgid', $data->orgs??[], $search_params['s_orgid']??'',
                                                        [
                                                        'class' => 'form-control small',
                                                        'placeholder' => '-любой-',
                                                        'onChange' => 'this.form.submit()',
                                                        ])
                                                        !!}
                                    </div>
                                </td>
                                <td class="row">
                                    <div class="input-group col-md-6">
                                        <input type="text" class="form-control c" name="s_num"
                                               value="{{$search_params['s_num'] ?? ''}}"
                                               placeholder="-номер карты-"/>
                                    </div>
                                    <div class="input-group col-md-6">
                                        <input type="text" class="form-control c" name="s_name"
                                               value="{{ $search_params['s_name'] ?? ''}}"
                                               placeholder="-название-"/>
                                    </div>
                                </td>

                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route('fuelcards.index') }}"
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
                                @if($item->orgid <> $cur_orgid)
                                    <tr style="background-color: #cbeef6">
                                        <td colspan="6">
                                            <a href="{{route('orgs.edit',$item->orgid)}}"
                                               target="_blank"><b>{{$item->org_name}}</b></a>
                                        </td>
                                    </tr>
                                    <?php
                                    $cur_orgid = $item->orgid;
                                    ?>
                                @endif
                                <tr style="background-color: {{$tr_bg_col}}">
                                    <td class="small" style="text-align: right'">
                                        {{$loop->index + $rec0}}
                                    </td>
                                    <td></td>
                                    <td colspan="">
                                        <a name="{{$item->id}}"/>
                                        <a href="{{ route('fuelcards.edit',$item->id)}}" style="">
                                            <b>{{$item->num}}</b> - {{$item->name}}
                                        </a>
                                        <div class="small">
                                            {{$item->notes}}
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('fuelcards.edit',$item->id)}}"
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
