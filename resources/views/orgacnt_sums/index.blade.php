<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Остатки на р/счетах";
    $thisSysObjCode = 'orgacnt_sums';
    ?>
    <link rel="stylesheet" href="/css/subnav.css">


    <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
        @csrf
        <div class="container">

            <?php
            $breadcrumbs = [
                'ФинМонитор' => "/finmon",
                $thisTitle => null,
            ];
            ?>
            @includeIf('layouts.breadcrumbs')


            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h3>{{$thisTitle}}</h3>

                    <div class="row mb-2">
                        <div class="col-md-8 ">
                            <div class="subnav shift">
                                <ul>
                                    <li><a href="{{route('orgplnpays.index')}}" title="План платежей">План платежей</a></li>
                                    <li><a href="{{route('invoices.index')}}" title="Счета от поставщиков">Счета</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>




                    <div class="mt-2">
                        @include('layouts.edit_msgs')

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'o.name','retroute'=>$thisSysObjCode.'.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Владелец {!! sort_mark('o.name',$sort_params) !!}</a>
                                </td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'oas.ondate','retroute'=>$thisSysObjCode.'.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Дата {!! sort_mark('oas.ondate',$sort_params) !!}</a>
                                </td>
                                <td>Р/счет</td>
                                <td class="text-right">
                                    Остаток на начало, руб
                                </td>
                                <td class="text-right">
                                    Приход, руб
                                </td>
                                <td class="text-right">
                                    Итого, руб
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
                                <td/>
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
                                <td>
                                    @if(isset($timestatuses) and $usrrights['alldates']??false)
                                        <div class="input-group">
                                            {!! Form::select('s_timestatuscode', $timestatuses, $search_params['s_timestatuscode']??'',
                            [
                            'class' => 'form-control small',
                            'placeholder' => '-все-',
                            'id' => 's_timestatuscode',
                            ])
                            !!}
                                            @endif
                                        </div>
                                        <input type="date" class="form-control c" name="s_plndate"
                                               id="s_plndate"
                                               value="{{ $search_params['s_plndate'] ?? ''}}"
                                               placeholder="-название-"
                                               STYLE="display: none;"/>
                                </td>
                                <td></td>

                                {{--								<td>--}}
                                {{--									<div class="input-group">--}}
                                {{--										{!! Form::select('s_orggrpid', $objgroups, $search_params['s_orggrpid'],--}}
                                {{--														[--}}
                                {{--														'class' => 'form-control small',--}}
                                {{--														])--}}
                                {{--														!!}--}}
                                {{--									</div>--}}
                                {{--								</td>--}}

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
                                            {{$item->orgname}}
                                        </td>
                                        <td>
                                            <a name="{{$item->id}}"/>
                                            <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="">
                                                {{date_create($item->ondate)->format('d.m.Y')}}
                                            </a>
                                        </td>
                                        <td class="text-left">
                                            {{$item->rs_num}}
                                            <div class="ml-2">{{$item->bankname}}</div>
                                        </td>
                                        <td class="text-right">
                                            {{number_format($item->restsum,2)}}
                                        </td>
                                        <td class="text-right">
                                            {{number_format($item->inpsum,2)}}
                                        </td>
                                        <td class="text-right">
                                            {{number_format($item->cursum,2)}}
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
