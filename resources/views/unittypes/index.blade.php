<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Единицы измерения";
    $thisSysObjCode = 'unittypes';
    //dd($usrrights);
    ?>


    <div class="container">

        <?php
        $breadcrumbs = [
            'Сервис' => "/admin",
            'Справочники' => "/admin?tab=nsi-dic",
            $thisTitle => null,
        ];
        ?>
        @includeIf('layouts.breadcrumbs')

        @includeIf('layouts.edit_msgs')

        <div class="row justify-content-center">
            <div class="col-md-12">
                <h3>{{$thisTitle}}</h3>
                <style>
                </style>

                <div class="mt-2">
                    <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
                        @csrf

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'ut.name','retroute'=>$thisSysObjCode.'.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Название {!! sort_mark('ut.name',$sort_params) !!}</a>

                                </td>
                                <td>Описание</td>
                                <td>Точность (знаков после запятой)</td>
                                <td class="text-right">Базовая ЕИ</td>

                                <td style="text-align: center;">
                                    @if ($usrrights['create'])
                                        <a href="{{ route($thisSysObjCode.'.create',0)}}"
                                           class="btn btn-warning btn-sm d-print-none"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>

                            <tr class="d-print-none small text-center">
                                <td></td>
                                <td colspan="2"><input type="text" class="form-control" name="s_name"
                                           value="{{ $search_params['s_name'] ?? ''}}"
                                           placeholder=""/></td>
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
                            <?php
                            $bgcols = array(
                                '#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC'
                            , '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7'
                            , '#aaccaa', '#bbccbb');

                            $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
                            ?>
                            @foreach($recs as $item)
                                <?php
                                $colshift = (1 - $item->active) * 2;
                                $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                                ?>
                                <tr style="background-color: {{$tr_bg_col}}">
                                    <td class="small text-right" style="color: #b7b2b2">
                                        {{$loop->index + $rec0}} <a name="{{$item->id}}"></a>
                                    </td>
                                    <td>
                                        <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="">
                                            <b>{{$item->name}}</b>
                                        </a>
                                    </td>
                                    <td class="small">
                                        {{$item->descript}}
                                    </td>
                                    <td class="small text-center">
                                        {{$item->decimal_dgts}}
                                    </td>
                                    <td class="text-right">
                                        @if(isset($item->parent_by))
                                            = {{rtrim($item->k2prnt_unit,'0')}}
                                            <a href="{{ route($thisSysObjCode.'.edit',$item->parent_by)}}" style="">
                                                 <b>{{$item->parent_name}}</b>
                                            </a>
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>

                                    <td style="text-align: center;">
                                        <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}"
                                           class="btn btn-sm btn-primary no-print d-print-none"
              me                             title="Просмотреть/Изменить запись">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </form>

                    <div>
                        {{$recs->links()}}
                    </div>
                </div>
            </div>
        </div>

        {{--        <script src="{{ asset('js/unittypes_index.js') }}" defer></script>--}}

    </div>

@endsection
