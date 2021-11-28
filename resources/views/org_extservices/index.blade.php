<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Остатки на субсчетах поставщиков";
    $thisSysObjCode = 'org_extservices';
    ?>

    <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
        @csrf
        <div class="container">


            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h3>{{$thisTitle}}</h3>
                    <a href="{{ route('extsrvc_sums.refresh',104)}}"
                       class="btn btn-success btn-sm "
                       title='Обновить данные из ЛК ОАО "ПриморНефтеПродукт"'>
                        <i class="fa fa-refresh"></i>
                    </a>
                    <div class="mt-2">
                        @include('layouts.edit_msgs')


                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'es.name','retroute'=>$thisSysObjCode.'.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Сервис
                                        (Субсчет) {!! sort_mark('es.name',$sort_params) !!}</a>

                                    <a href="{{ route('set_sort',['field' => 'so.name','retroute'=>$thisSysObjCode.'.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Поставщик {!! sort_mark('so.name',$sort_params) !!}</a>
                                </td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'o.name','retroute'=>$thisSysObjCode.'.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Получатель {!! sort_mark('o.name',$sort_params) !!}</a>
                                </td>
                                <td class="text-right">
                                    Остаток, руб
                                </td>
                                <td class="text-right">
                                    Лимит блокировки, руб
                                </td>
                                <td class="text-right">
                                    Лимит уведомления, руб
                                </td>
                                <td style="text-align: center;">
                                    @if ($usrrights['create'])
                                        <a href="{{ route($thisSysObjCode.'.create')}}"
                                           class="btn btn-warning btn-sm"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>

                            <tr style="text-align: center;" class="d-print-none">
                                <td></td>
                                <td></td>
                                <td class="">
                                    @if(isset($usedorgs))
                                        {!! Form::select('s_ownorgid',
                                         $usedorgs,
                                         $search_params['s_ownorgid']??'',
                                        ['class' => 'form-control',
                                        'placeholder'=>'-все-']) !!}
                                    @endif
                                    {{--                                    <input type="text" class="form-control c" name="s_name"--}}
                                    {{--                                           value="{{ $search_params['s_name'] ?? ''}}"--}}
                                    {{--                                           placeholder="-название-"/>--}}
                                </td>
                                <td></td>
                                <td></td>
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
                            @if(count($recs)>0)
                                <?php
                                $bgcols = array(
                                    '#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC'
                                , '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7'
                                , '#aaccaa', '#bbccbb');

                                $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
                                $curOwnMark = "";
                                ?>
                                @foreach($recs as $item)
                                    <?php
                                    $colshift = (1 - $item->active) * 2;
                                    $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                                    ?>
                                    <tr style="background-color: {{$tr_bg_col}}">
                                        <td class="small" style="text-align: right'">
                                            {{$loop->index + $rec0}}
                                        </td>
                                        <td class="text-left">
                                            {{$item->name}}
                                            <div>{{$item->srvcorgname}}</div>
                                        </td>
                                        <td class="text-left">
                                            {{$item->orgname}}
                                        </td>
                                        <td class="text-right">
                                            <b>{{number_format($item->rest_sum,2)}}</b>
                                            <div class="small mt-2">
                                                {{date_create($item->rest_dt)->format('d.m.Y H:i')}}
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            {{number_format($item->lock_limsum,2)}}
                                            <div class="small mt-2">
                                                осталось: {{$item->rest_sum-$item->lock_limsum}}
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            {{number_format($item->notify_limsum,2)}}
                                        </td>

                                        <td style="text-align: center;">
                                            <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}"
                                               class="btn btn-sm btn-primary"
                                               title="Просмотреть/Изменить запись">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center font-weight-bold">Нет данных</td>
                                </tr>
                            @endif

                            </tbody>
                        </table>

                        <div>
                            {{$recs->links()}}
                        </div>
                    </div>
                </div>
    </form>
    <script src="{{ asset('js/mchnrqst_index.js') }}" defer></script>
@endsection
