@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Контрагенты";
    ?>
    <link rel="stylesheet" href="/css/subnav.css">
    <style>
        .owngrp {
            background-color: #ffffcc !important;
        }
    </style>

    <form name="forIndex" id="forIndex" method="post" action="{{ route('orgs.search') }}">
        @csrf
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

                    <div class="row mb-2">
                        <div class="col-md-8 ">
                            <div class="subnav shift">
                                <ul>
                                    @if(1==0 and \Illuminate\Support\Facades\Route::has('contracts.index'))
                                        <li><a href="{{route('contracts.index')}}"
                                               title="Договоры с контрагентами">Договоры</a>
                                        </li>
                                    @endif
                                    @if(\Illuminate\Support\Facades\Route::has('orgstaff.index'))
                                        <li><a href="{{route('orgstaff.index')}}"
                                               title="Персонал организации">Персонал</a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                        <div class="input-group col-md-4">
                        </div>
                    </div>


                    <div class="mt-3">

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td><a href="{{ route('set_sort',['field' => 'o.name','retroute'=>'orgs.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Название {!! sort_mark('o.name',$sort_params) !!}</a>,
                                    <a href="{{ route('set_sort',['field' => 'o.inn','retroute'=>'orgs.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">
                                        ИНН/КПП {!! sort_mark('o.inn',$sort_params) !!}
                                    </a></td>
                                <td><a href="{{ route('set_sort',['field' => 'o.address','retroute'=>'orgs.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Адрес {!! sort_mark('o.address',$sort_params) !!}</a></td>
                                <td>Руководитель</td>
                                <td>Группы</td>

                                <td style="text-align: center;">
                                    @if ($usrrights['create'])
                                        <a href="{{ route('orgs.create',0)}}"
                                           class="btn btn-warning btn-sm"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>

                            <tr style="text-align: center;">
                                <td/>
                                <td class="row">
                                    <div class="input-group col-md-12">
                                        <input type="text" class="form-control c" name="s_name"
                                               value="{{ $search_params['s_name'] ?? ''}}"
                                               placeholder="-название/ИНН/КПП/...-"/>
                                    </div>
                                    {{--                                    <div class="input-group col-md-5">--}}
                                    {{--                                        <input type="text" class="form-control c" name="s_inn"--}}
                                    {{--                                               value="{{$search_params['s_inn'] ?? ''}}"--}}
                                    {{--                                               placeholder="-ИНН-"/>--}}
                                    {{--                                    </div>--}}
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control c" name="s_addr"
                                               value="{{$search_params['s_addr'] ?? ''}}"
                                               placeholder="-адрес-"/>
                                    </div>

                                </td>
                                <td>
                                    <input type="text" class="form-control c" name="s_boss_name"
                                           value="{{ $search_params['s_boss_name'] ?? ''}}"
                                           placeholder=""/>
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_orggrpid', $orggroups, $search_params['s_orggrpid'],
                                                        [
                                                        'class' => 'form-control small',
                                                        ])
                                                        !!}
                                        {!! Form::select('s_flagtypeid', $usedflags, $search_params['s_flagtypeid']??'',
                                                        [
                                                        'class' => 'form-control small',
                                                        'placeholder' => '',
                                                        ])
                                                        !!}
                                        {!! Form::select('s_rating', $ratings, $search_params['s_rating']??'',
                                                        [
                                                        'class' => 'form-control small',
                                                        'placeholder' => '',
                                                        ])
                                                        !!}
                                    </div>

                                </td>
                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route('orgs.index') }}"
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
                            $curOwnMark = "";
                            ?>
                            @foreach($recs as $org)
                                <?php
                                $colshift = (1 - $org->active) * 2;
                                $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                                $OwnMark = $org->ownmark;
                                if ($OwnMark > 1) $OwnMark = 1;
                                ?>
                                @if ($OwnMark <> $curOwnMark )
                                    <?php
                                    if ($OwnMark == 1)
                                        $ownGrpName = 'Холдинг';    //"Владельцы";
                                    else
                                        $ownGrpName = "Клиенты";
                                    ?>
                                    <tr class="owngrp">
                                        <td colspan="5"><h4>{{$ownGrpName}}</h4></td>
                                        <td>
                                            @if ($usrrights['save'])
                                                <a href="{{ route('orgs.create',$OwnMark)}}"
                                                   class="btn btn-warning btn-sm"
                                                   title="Добавить запись">
                                                    <i class="fa fa-plus"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    <?php
                                    $curOwnMark = $OwnMark;
                                    ?>
                                @endif
                                <tr style="background-color: {{$tr_bg_col}}">
                                    <td class="small" style="text-align: right'">
                                        {{$loop->index + $rec0}}
                                    </td>
                                    <td>
                                        <a name="{{$org->id}}"></>
                                        <a href="{{ route('orgs.edit',$org->id)}}" style="">
                                            {{$org->name}}
                                        </a>
                                        <div class="small">
                                            <?php
                                            $codes = '';
                                            if (isset($org->inn))
                                                $codes .= ' ИНН:' . $org->inn;
                                            if (isset($org->kpp))
                                                $codes .= ' КПП:' . $org->kpp;
                                            if (isset($org->okpo) and $org->okpo <> '')
                                                $codes .= ' ОКПО:' . $org->okpo;
                                            if (isset($org->ogrn))
                                                $codes .= ' ОГРН:' . $org->ogrn;
                                            if (isset($org->ogrnip))
                                                $codes .= ' ОГРНИП:' . $org->ogrnip;
                                            ?>
                                            {{$codes}}
                                        </div>
                                    </td>
                                    <td class="small text-center">
                                        {{$org->address}}
                                    </td>
                                    <td class="small text-center">
                                        {{--										{{$org->lstCurator}}--}}
                                        {{$org->boss_postname}} {{$org->boss_name}}
                                    </td>
                                    <td class="small text-center">
                                        <a href="{{route('org_groups.edit',$org->id)}}">{!! $org->lstGroups !!}
                                            ...</a>
                                        {{$org->main_activity}}<br>
                                        {!! $org->lstflags !!}
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('orgs.edit',$org->id)}}"
                                           class="btn btn-sm btn-primary"
                                           title="Просмотреть/Изменить запись">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <div>
                            @if (1==0 and Route::currentRouteName() == "orgs.search")
                                {{$recs->appends(['searchval'=>$searchval
                                ,'searchinn'=>$searchinn
                                ,'s_address'=>$s_address
                                ,'s_statuscode'=>$s_statuscode
                                ])->links()}}
                            @else
                                {{$recs->links()}}
                            @endif
                        </div>
                    </div>
                </div>
    </form>
@endsection

