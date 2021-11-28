<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Спецтехника";
    $thisSysObjCode = 'machines';
    ?>

    <form name="forIndex" id="forIndex" method="post" action="{{ route('machines.index') }}">
        @csrf
        <div class="container">

            <?php
            $breadcrumbs = [
                'Справочники' => "http://basco.test/admin?tab=nsi-dic",
                $thisTitle => null,
            ];
            ?>
            @includeIf('layouts.breadcrumbs')

            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h3>{{$thisTitle}}</h3>
                    <div class="mt-2">

                        @include('layouts.edit_msgs')

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td class="small">#</td>
                                <td>Владелец</td>
                                <td><a href="{{ route('set_sort',['field' => 'm.name','retroute'=>'machines.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Название {!! sort_mark('m.name',$sort_params) !!}</a>
                                </td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'm.regnum','retroute'=>'machines.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Рег. № {!! sort_mark('m.regnum',$sort_params) !!}</a>
                                </td>

                                <td>
                                    <a href="{{ route('set_sort',['field' => 'mt.name','retroute'=>'machines.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Тип {!! sort_mark('mt.name',$sort_params) !!}</a>
                                </td>

                                <td style="text-align: center;">
                                    @if ($usrrights['create'])
                                        <a href="{{ route('machines.create',0)}}"
                                           class="btn btn-warning btn-sm"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>

                            <tr style="text-align: center;">
                                <td></td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_orgid', $data->orgs, $search_params['s_orgid']??'',
                                                        [
                                                        'class' => 'form-control small',
                                                        'placeholder' => '-любой-',
                                                        'onChange' => 'this.form.submit()',
                                                        ])
                                                        !!}
                                    </div>
                                </td>
                                <td class="row">
                                    <div class="input-group col-md-7">
                                        <input type="text" class="form-control c" name="s_name"
                                               value="{{ $search_params['s_name'] ?? ''}}"
                                               placeholder="-название-"/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control c" name="s_regnum"
                                               value="{{$search_params['s_regnum'] ?? ''}}"
                                               placeholder="-рег.номер-"/>
                                    </div>

                                </td>

                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_type', $usedmchntypes, $search_params['s_type']??'',
                                                        [
                                                        'class' => 'form-control small',
                                                        'placeholder' => '-',
                                                        'onChange' => 'this.form.submit()',
                                                        ])
                                                        !!}
                                    </div>

                                </td>
                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route('machines.index') }}"
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
                                        <a href="{{ route('machines.edit',$item->id)}}" style="">
                                            {{$item->name}}
                                        </a>
                                        <div class="small">
                                            {{$item->descript}}
                                        </div>
                                    </td>
                                    <td class="small text-center font-weight-bold">
                                        {{$item->regnum}}
                                    </td>
                                    <td class="small text-left">
                                        {{$item->typename}}
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('machines.edit',$item->id)}}"
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
