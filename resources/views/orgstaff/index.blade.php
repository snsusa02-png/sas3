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
        $thisSysObjCode = 'orgstaff';

        ?>
        <link rel="stylesheet" href="/css/subnav.css">

        <form name="forIndex" id="forIndex" method="post" action="{{ route('orgstaff.index') }}">
            @csrf
            <div class="container">

                <?php
                $thisTitle = "Сотрудники";

                $breadcrumbs = [
                    'Сервис' => "/admin",
                    'Справочники' => "/admin?tab=nsi-dic",
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
                                        <li><a href="{{route('orgs.index')}}"
                                               title="Организации">Организации</a>
                                        </li>
                                        @if(1==0)
                                            <li><a href="{{route('jobtimesheets.index')}}"
                                                   title="Учет рабочего времени">Учет времени</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            <div class="form-group offset-md-0 col-md-3">
                                <label for="descript">Образ документа:</label>
                                {!! Form::select('s_file_doctypeid',  $data->file_doctypes??[], $data->search_params['s_file_doctypeid'] ?? '',
             [
             'class' => 'form-control',
             'placeholder' => '-',
             'title' => 'Тип документа, приложенного к записи о сотруднике',
              'data-toggle' => 'tooltip',
             ]) !!}
                            </div>
                        </div>

                        <div class="mt-3">
                            @include('layouts.edit_msgs')
                        </div>

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>Организация</td>
                                <td>ФИО</td>
                                <td>Должность</td>
                                <td>Статус</td>
                                <td style="text-align: center;">
                                    @if($usrrights['create']??false)

                                        <a href="{{ route($thisSysObjCode.'.create',0)}}"
                                           class="btn btn-warning btn-sm">
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
                                <td colspan="2">
                                    <div class="input-group">
                                        {!! Form::select('s_orgflagid', [12=>'ГК '], $data->search_params['s_orgflagid']??'',
[
                                                        'class' => 'form-control small',
                                                        'style' => 'max-width:108px',
                                                        'placeholder' => '-',
                                                        'onChange' => 'this.form.submit()',
                                                        ])
                                                        !!}
                                        {!! Form::select('s_orgid', $data->ownorgs, $data->search_params['s_orgid']??'',
                                                        [
                                                        'class' => 'form-control small',
                                                        'placeholder' => '-',
                                                        'onChange' => 'this.form.submit()',
                                                        ])
                                                        !!}
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="s_name"
                                               value="{{$data->search_params['s_name']??''}}"/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="s_postname"
                                               value="{{$data->search_params['s_postname']??''}}"/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_active', $data->statuses??[], $data->search_params['s_active']??'',
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
                                                formaction="{{ route('orgstaff.index') }}"
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
                            $retURL = Request::url();
                            ?>
                            @foreach($recs as $rec)
                                @if($rec->orgid<>$curOrgId)
                                    <tr>
                                        <td colspan="6" class="font-weight-bold"><a
                                                href="{{route('orgs.edit',$rec->orgid)}}">{{$rec->org_name}}</a>
                                            @if($usrrights['create']??false)
                                                <div class="float-right">
                                                    <a href="{{ route('orgstaff.create',$rec->orgid)}}"
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
                                <?php
                                if ($rec->active == 1) {
                                    $status_css = '';
                                    $status_name = '';
                                } else {
                                    $status_css = 'color:darkred;';
                                    $status_name = 'архив';
                                }
                                ?>
                                <tr>
                                    <td class="small" style="text-align: right'">
                                        {{$data->rec0++}}
                                    </td>
                                    <td></td>
                                    <td>
                                        <a href="{{route('orgstaff.edit',$rec->id)}}">{{$rec->lname}} {{$rec->fname}} {{$rec->mname}}</a>
                                    </td>
                                    <td>{{$rec->post_name??$rec->postname}}</td>
                                    <td style="{{$status_css}}">{{$status_name}}</td>

                                    <td style="text-align: center;">
                                        <a href="{{ route('orgstaff.edit',$rec->id)}}?returl={{$retURL}}"
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

