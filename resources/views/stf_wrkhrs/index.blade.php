<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    @guest
        <?php
        redirect(route('login'));
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();
        ?>
    @else
        <?php
        $thisSysObjCode = 'stf_wrkhrs';
        $retURL = Request::url();
        ?>
        <link rel="stylesheet" href="/css/subnav.css">

        <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
            @csrf
            <div class="container">

                <?php
                $thisTitle = "Учет рабочего времени сотрудников";

                $breadcrumbs = [
                    'Сервис' => "/admin",
                    'ЗП' => "/admin?tab=nsi-salary",
                    $thisTitle => null,
                ];
                ?>
                @includeIf('layouts.breadcrumbs')

                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <h3>{{$thisTitle}}</h3>

                        <div class="row mb-2">
                            <div class="col-md-9 ">
                                <div class="subnav shift">
                                    <ul>
                                        <li><a href="{{route('chargetypes.index')}}"
                                               title="Виды начислений/удержаний">Виды начислений</a>
                                        </li>
                                        <li><a href="{{route('orgstaff.index')}}"
                                               title="Персонал организаций">Персонал</a>
                                        </li>
                                        @if(1==1)
                                            <li><a href="{{route('reports.rep56')}}"
                                                   title="Ведомость учета начислений/удержаний">Ведомость</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            @include('layouts.edit_msgs')
                        </div>

                        <table class="table table-striped table-sm">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>Организация, Период</td>
                                <td>Сотрудник</td>
                                <td>Часы * Ставка</td>
                                <td>Сумма, руб</td>
                                <td style="text-align: center;">
                                    @if($usrrights['create']??false)
                                        <a href="{{ route($thisSysObjCode.'.create',0)}}?returl={{$retURL}}"
                                           class="btn btn-warning btn-sm">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            <tr style="text-align: center;">
                                <td colspan="2">
                                    <div class="input-group">
                                        {!! Form::select('s_orgid', $data->ownorgs, $data->search_params['s_orgid']??'',
                                                        [
                                                        'class' => 'form-control small',
                                                        'placeholder' => '-',
                                                        'onChange' => 'this.form.submit()',
                                                        ])
                                                        !!}
                                    </div>
                                    <div class="input-group">
                                        {!! Form::select('s_ym', $data->yms??[], $data->search_params['s_ym'],
                                                            [
                                                            'id' => 's_ym',
                                                            'class' => 'form-control',
                                                            'placeholder' => '-укажите-',
                                                            'onChange' => 'this.form.submit()',
                                                            ])
                                                            !!}
                                    </div>
                                </td>
                                <td><div class="input-group">
                                        <input type="text" class="form-control" name="stf_name"
                                               value="{{$data->search_params['stf_name']??''}}"/>
                                    </div>
                                </td>
                                <td>
                                </td>
                                <td>
                                </td>
                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-success"
                                                formaction="{{ route($thisSysObjCode.'.index') }}"
                                                formmethod="post">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </thead>
                            <tbody>

                            <?php
                            $curOrgId = -1;
                            $curYrMn = '0000/00';
                            ?>
                            @foreach($recs as $rec)
                                @if($rec->orgid<>$curOrgId)
                                    <tr>
                                        <td colspan="7" class="font-weight-bold"><a
                                                href="{{route('orgs.edit',$rec->orgid)}}">{{$rec->org_name}}</a>
                                            @if($usrrights['create']??false)
                                                <div class="float-right">
                                                    <a href="{{ route('stf_wrkhrs.create',0)}}?returl={{$retURL}}"
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    <?php
                                    $curOrgId = $rec->orgid;
                                    $curYrMn = '0000/00';
                                    ?>
                                @endif
                                @if(($rec->yr.'/'.$rec->mn) <> $curYrMn)
                                    <tr>
                                        <td colspan="7" class="font-weight-bold"><span
                                                style="margin-left: 20px;">&nbsp;</span>
                                            {{$rec->yr.' / '.$rec->mn}}
                                        </td>
                                    </tr>
                                    <?php
                                    $curYrMn = $rec->yr . '/' . $rec->mn;
                                    $npp = 1;
                                    ?>
                                @endif
                                <tr>
                                    <td class="small" style="text-align: right'">
                                        {{--                                        {{$data->rec0++}}--}}
                                        {{$npp++}}
                                    </td>
                                    <td></td>
                                    <td><a href="{{route('stf_wrkhrs.edit',$rec->id)}}">{{$rec->stf_name}}</a>
                                    </td>
                                    <td class="text-center">{{$rec->day_tot_hrs}} * {{$rec->day_hr_cost}}</td>
                                    <td class="text-right" style="color:darkgreen;">{{$rec->tot_sum}}</td>

                                    <td style="text-align: center;">
                                        <a href="{{ route('stf_wrkhrs.edit',$rec->id)}}?returl={{$retURL}}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @include('layouts.paginate_links')
                    </div>
                </div>
            </div>
            </div>
        </form>
    @endguest
@endsection

