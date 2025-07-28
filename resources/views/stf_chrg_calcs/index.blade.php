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
        $thisSysObjCode = 'stf_chrg_calcs';
        $retURL = Request::url();

        ?>
        <link rel="stylesheet" href="/css/subnav.css">

        <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
            @csrf
            <div class="container">

                <?php
                $thisTitle = "Фактические начисления/удержания, применённые к сотрудникам организаций холдинга";

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
                                <td>Организация, Сотрудник</td>
                                <td>Учет. дата</td>
                                <td>Вид</td>
                                <td>Начисление, руб</td>
                                <td>Удержание, руб</td>
                                <td style="text-align: center;">
                                    @if($usrrights['create']??false)
                                        <a href="{{ route($thisSysObjCode.'.create',0)}}?returl={{$retURL}}"
                                           class="btn btn-warning btn-sm">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                    @if ($usrrights['load']??false)
                                        <a href="{{ route($thisSysObjCode.'.load')}}"
                                           class="btn btn-success btn-sm"
                                           title="Загрузить записи о начисленных удержаниях сотрудников в файла XLS">
                                            <i class="fa fa-upload" aria-hidden="true"></i>
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
                                        <input type="text" class="form-control" name="stf_name"
                                               value="{{$data->search_params['stf_name']??''}}"/>
                                    </div>
                                </td>
                                <td>
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
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="chargetype_name"
                                               value="{{$data->search_params['chargetype_name']??''}}"/>
                                    </div>
                                </td>
                                <td colspan="2">
                                    <div class="input-group">
                                        {!! Form::select('charge_dir', $data->dirs, $data->search_params['charge_dir']??'',
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
                            $curStaffId = -1;
                            ?>
                            @foreach($recs as $rec)
                                @if($rec->orgid<>$curOrgId)
                                    <tr>
                                        <td colspan="7" class="font-weight-bold"><a
                                                href="{{route('orgs.edit',$rec->orgid)}}">{{$rec->org_name}}</a>
                                            @if($usrrights['create']??false)
                                                <div class="float-right">
                                                    <a href="{{ route('stf_chrg_calcs.create',0)}}?returl={{$retURL}}"
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    <?php
                                    $curOrgId = $rec->orgid;
                                    ?>
                                @endif
                                @if($rec->staffid<>$curStaffId)
                                    <tr>
                                        <td colspan="7" class="font-weight-bold"><span
                                                style="margin-left: 20px;">&nbsp;</span>
                                            <a href="{{route('orgstaff.edit',$rec->staffid)}}">{{$rec->stf_name}}</a>
                                            @if($usrrights['create']??false)
                                                <div class="float-right">
                                                    <a href="{{ route('stf_chrg_calcs.create',$rec->staffid)}}?returl={{$retURL}}"
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    <?php
                                    $curStaffId = $rec->staffid;
                                    $npp = 1;
                                    ?>
                                @endif
                                <tr>
                                    <td class="small" style="text-align: right'">
                                        {{--                                        {{$data->rec0++}}--}}
                                        {{$npp++}}
                                    </td>
                                    <td></td>
                                    {{--                                    <td class="small">{{date_format(date_create($rec->forbegdate), 'd.m.Y')}}--}}
                                    {{--                                            - {{isset($rec->enddate)?date_format(date_create($rec->forenddate), 'd.m.Y'):'...'}}</td>--}}
                                    <td class="small">{{date_format(date_create($rec->docdate), 'd.m.Y')}}</td>

                                    {{--                                    <td>{{$data->dirs[$rec->charge_dir]??'-'}}: {{$rec->chargetype_name}}</td>--}}
                                    <td><a href="{{ route('stf_chrg_calcs.edit',$rec->id)}}?returl={{$retURL}}"
                                           class="">{{$rec->chargetype_name}}</a>
                                        <div class="small" style="margin-left:16px; color:gray;">
                                            <?php echo str_replace(chr(13) . chr(10), '<br>', $rec->notes) ?>
                                        </div>
                                    </td>

                                    @if($rec->charge_dir > 0 )
                                        <td class="text-right" style="color:darkgreen;">{{$rec->charge_sum}}</td>
                                        <td class="text-right"></td>
                                    @else
                                        <td class="text-right"></td>
                                        <td class="text-right" style="color:darkred;">{{$rec->charge_sum}}</td>
                                    @endif

                                    <td style="text-align: center;">
                                        <a href="{{ route('stf_chrg_calcs.edit',$rec->id)}}?returl={{$retURL}}"
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

