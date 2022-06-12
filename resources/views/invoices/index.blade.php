<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Счета";
    $thisSysObjCode = 'invoices';
    ?>
    <link rel="stylesheet" href="/css/subnav.css">

    <div class="container">
        <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
            @csrf

            <?php
            $breadcrumbs = [
                'Данные' => "/rqsts?tab=nsi-pays",
                'Взаиморасчеты' => "/rqsts?tab=nsi-pays",
                $thisTitle => null,
            ];
            ?>
            @includeIf('layouts.breadcrumbs')

            <div class="row ">
                <div class="col-md-4">
                    <h3>{{$thisTitle}}</h3>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-8 ">
                    <div class="subnav shift">
                        <ul>
                            {{--                            <li><a href="{{route('orgplnpays.index')}}" title="План платежей">План платежей</a></li>--}}
                            <li><a href="{{route('paydocs.index')}}" title="Патежи">Платежи</a></li>
                            <li><a href="{{route('mchn_raids.index')}}" title="Перевозки">Перевозки</a></li>
                            <li><a href="{{route('refitems.index')}}" title="Справочник номенклатуры">Номенклатура</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-12 mt-2">

                    @include('layouts.edit_msgs')

                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <td>#</td>
                            <td>
                                <a href="{{ route('set_sort',['field' => 'pp.doctypeid','retroute'=>$thisSysObjCode.'.index']) }}"
                                   class="btn btn-sm"
                                   title="Сортировать">Вид док-та{!! sort_mark('pp.doctypeid',$sort_params) !!}</a>
                            </td>
                            <td>
                                <a href="{{ route('set_sort',['field' => 'pp.docnum','retroute'=>$thisSysObjCode.'.index']) }}"
                                   class="btn btn-sm"
                                   title="Сортировать">№ {!! sort_mark('pp.docnum',$sort_params) !!}</a>,
                                <a href="{{ route('set_sort',['field' => 'pp.docdate','retroute'=>$thisSysObjCode.'.index']) }}"
                                   class="btn btn-sm"
                                   title="Сортировать">Дата {!! sort_mark('pp.docdate',$sort_params) !!}</a>
                                , описание, действителен до
                            </td>
                            <td>
                                <a href="{{ route('set_sort',['field' => 'oo.name','retroute'=>$thisSysObjCode.'.index']) }}"
                                   class="btn btn-sm"
                                   title="Сортировать">Плательщик {!! sort_mark('oo.name',$sort_params) !!}</a>,
                                Категория
                            </td>
                            <td>
                                <a href="{{ route('set_sort',['field' => 'o.name','retroute'=>$thisSysObjCode.'.index']) }}"
                                   class="btn btn-sm"
                                   title="Сортировать">Поставщик {!! sort_mark('o.name',$sort_params) !!}</a>
                            </td>

                            <td class="text-right">
                                Сумма документа / использования, руб
                            </td>
                            <td class="text-right">
                                Оплата, руб
                            </td>
                            <td class="text-center">Статус</td>

                            <td style="text-align: center;">
                                @if ($usrrights['create'])
                                    <a href="{{ route($thisSysObjCode.'.create',0)}}"
                                       class="btn btn-warning btn-sm"
                                       title="Добавить запись">
                                        <i class="fa fa-plus"></i>
                                    </a>
{{--                                    <a href="{{ route($thisSysObjCode.'.load')}}"--}}
{{--                                       class="btn btn-success btn-sm"--}}
{{--                                       title="Загрузить счет в формате файла XLS">--}}
{{--                                        <i class="fa fa-plus"></i>--}}
{{--                                    </a>--}}
                                @endif
                            </td>
                        </tr>

                        <tr style="text-align: center;" class="d-print-none">
                            <td/>
                            <td>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="s_docnum"
                                       value="{{ $search_params['s_docnum'] ?? ''}}"
                                       placeholder="-№-"
                                       STYLE=""/>

                                @if(isset($timestatuses))
                                    <div class="input-group">
                                        {!! Form::select('s_timestatuscode', $timestatuses, $search_params['s_timestatuscode']??'',
                        [
                        'class' => 'form-control small',
                        'placeholder' => '-все-',
                        'id' => 's_timestatuscode',
                        //'onChange' => 'this.form.submit()',
                        ])
                        !!}

                                    </div>
                                @endif
                            </td>

                            <td class="">
                                @if(isset($usedorgs))
                                    {!! Form::select('s_ownorgid',
                                     $usedorgs,
                                     $search_params['s_ownorgid']??'',
                                    ['class' => 'form-control',
                                    'onChange' => 'this.form.submit()',
                                    'placeholder'=>'-все-']) !!}
                                @endif
                                @if(isset($data->categories))
                                    {!! Form::select('s_categoryid', $data->categories, $search_params['s_categoryid']??'',
                                    [
                                    'class' => 'form-control small',
                                    'placeholder' => '-',
                                    ])
                                    !!}
                                @endif
                            </td>
                            {{--								<td>--}}
                            {{--									<div class="input-group">--}}
                            {{--										{!! Form::select('s_orggrpid', $objgroups, $search_params['s_orggrpid'],--}}
                            {{--														[--}}
                            {{--														'class' => 'form-control small',--}}
                            {{--														])--}}
                            {{--														!!}--}}
                            {{--									</div>--}}
                            {{--								</td>--}}

                            <td>
                                <input type="text" class="form-control c" name="s_orgname"
                                       value="{{ $search_params['s_orgname'] ?? ''}}"
                                       placeholder="-название-"/>
                            </td>
                            <td>@if(isset($data->usedsum_balances))
                                    {!! Form::select('s_usedsum_balance',
                                     $data->usedsum_balances,
                                     $search_params['s_usedsum_balance']??'',
                                    ['class' => 'form-control',
                                    'placeholder'=>'-']) !!}
                                @endif
                            </td>
                            <td></td>
                            <td>
                                {!! Form::select('s_status',
 $data->statuses,
 $search_params['s_status']??'',
['class' => 'form-control',
'onChange' => 'this.form.submit()',
'placeholder'=>'-все-']) !!}

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
                                $usedsum_css = ($item->usedsum == $item->docsum) ? 'color:green;' : "color:red;";
                                ?>
                                <tr style="background-color: {{$tr_bg_col}}">
                                    <td class="small" style="text-align: right'">
                                        {{$loop->index + $rec0}}
                                        <a name="{{$item->id}}"/>
                                    </td>
                                    <td class="small" style="text-align: right'">
                                        {{$data->doctypes[$item->doctypeid]??''}}
                                    </td>
                                    <td>
                                        <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="">
                                            №{{$item->docnum}} от {{date_create($item->docdate)->format('d.m.Y')}}
                                        </a>
                                        <div class="ml-3 small">
                                            {{$item->notes}}
                                        </div>
                                        @if(isset($item->enddate))
                                            действителен до: {{date_create($item->enddate)->format('d.m.Y')}}
                                        @endif
                                    </td>
                                    <td class="small text-left">
                                        {{$item->ownorgname}}
                                        @if(isset($item->for_orgid))
                                            <div class="ml-2">( {{$item->fororgname}} )</div>
                                        @endif
                                        <div class="ml-2 mt-1 font-italic">{{$item->category_name}}</div>
                                    </td>
                                    <td class="small text-left">
                                        {{$item->orgname}}
                                    </td>
                                    <td class="text-right">
                                        {{number_format($item->docsum,2)}}
                                        <div
                                            style="{{$usedsum_css}}">{{number_format($item->usedsum+$item->aux_sum,2)}}</div>
                                    </td>
                                    @php($tclass=($item->fctpaysum>=$item->docsum)?'text-success':'text-danger')
                                    <td class="text-right">
                                        <span class=" {{$tclass}}">{{number_format($item->fctpaysum,2)}}</span>
                                        <div class="small mt-1">{{$item->fctpay_at}}</div>
                                    </td>
                                    <td class="text-center">
                                        {{$item->status}}
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
                                <td colspan="10" class="text-center font-weight-bold">Нет данных</td>
                            </tr>
                        @endif

                        </tbody>
                    </table>

                    @includeIf('layouts.paginate_links')
                </div>
            </div>
    </div>
    </form>
    <script src="{{ asset('js/mchnrqst_index.js') }}" defer></script>
    </div>
@endsection

@section('title')
    {{$thisTitle}}
@endsection
