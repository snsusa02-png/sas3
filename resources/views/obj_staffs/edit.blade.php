@extends('layouts.edit')

@section('content')

    @if (!isset( $rec ))
        <?php
        redirect()->route('/');
        header("Location:/");
        die();
        ?>
    @else
        <?php
        $sysobjid = 1601;
        $thisSysObjCode = 'obj_staffs';
        $sysobjcode = $thisSysObjCode;
        $thisTitle = "Работник";


        $retURL = \Request::get('returl') ?? $rec->retURL ?? (route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id);

        $sysobjlbl = '*-*-*-*'; //todo: определить в контролере

        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .btn {
                margin-bottom: 4px;
            }

        </style>
        <div class="container">

            <div class="row">
                <div class="col-md-9 col-sm-12">

                    <div class="card mt-3">

                        @includeIf('layouts.edit_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', [$rec->id, $rec->sysobjid, $rec->objid]) }}">

                            @method('PUT')
                            @csrf
                            {{ Form::hidden('returl', $rec->retURL) }}
                            {{ Form::hidden('sysobjid', $rec->sysobjid) }}
                            {{ Form::hidden('objid', $rec->objid) }}

                            <div class="card-header" style="background-color: #fff5b1;">
                                {{$thisTitle}}

                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right;"
                                   href="{{ $retURL }}"
                                   title="Вернуться к списку">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>

                            <div class="card-body">

                                @include('layouts.err_msgs')

                                <div class="form-group">
                                    <label for="name">Объект Информационной Системы:</label>
                                    <b>{{$rec->sysobj->name}}</b>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="name">Персона, Роль, Основание, Подпись:</label>
                                        <?php
                                        $imax = ($rec->id == -1) ? 7 : 1;
                                        $rec->signs = [0 => 'нет', 1 => 'есть', 2 => 'не требуется'];
                                        //dd($rec);
                                        ?>
                                        @if ($usrrights['save'])

                                            @for ($i = 0; $i < $imax; $i++)
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="username[]"
                                                           class="ac_orgstaff_name form-control"
                                                           placeholder="-ФИО-"
                                                           value="{{$rec->orgstaff->name}}">
                                                    <input type="text"
                                                           class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3; " readonly>
                                                    <input type="hidden" name="staffid[]"
                                                           class="ac_orgstaff_id"
                                                           value="{{$rec->staffid}}">

                                                    <span style='width:190px'>
                                                        @if(1==0)
                                                            {!! Form::select('roletypeid[]', $rec->roletypes??[], $rec->roletypeid,
                                                                     [
                                                                     'class' => 'form-control',
                                                                     'placeholder' => '',
                                                                    ]) !!}
                                                        @else
                                                            <input type="text" name="roletypename[]"
                                                                   class="ac_roletypename form-control"
                                                                   placeholder="-роль-"
                                                                   value="{{$rec->roletype->name??$rec->rolename}}">
                                                            <input type="text"
                                                                   class="form-control text-center small roletype_ac_status"
                                                                   style="display: none; border: #d7f3e3; " readonly>
                                                            <input type="hidden" name="roletypeid[]"
                                                                   class="ac_roletypeid"
                                                                   value="{{$rec->roletypeid}}">
                                                        @endif
                                                    </span>
                                                    <span style='width:190px'>
                                                        <input type="text" class="form-control" name="reason[]"
                                                               placeholder="-основание-"
                                                               value="{{$rec->reason}}"/>
                                                    </span>
                                                    {!! Form::select('signed[]', $rec->signs??[], $rec->signed,
                                                                     [
                                                                     'class' => 'form-control',
                                                                     'placeholder' => '- наличие подписи -',
                                                                    ]) !!}
                                                </div>
                                            @endfor
                                        @else
                                            <div class="font-weight-bold">{{$rec->user->name}}</div>
                                        @endif
                                    </div>
                                </div>

                                @if(1==0)
                                    <div class="form-group">
                                        <label for="name">Роль, задача:</label>
                                        <input type="text" class="form-control" name="rolename"
                                               {{$inputReadOnly}}
                                               value="{{old('rolename',$rec->rolename)}}"/>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="form-group col-md-10">
                                            <label for="name">Организация:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('orgid', $rec->orgs, $rec->orgid,
                                                     [
                                                     'id' => 'orgid',
                                                     'class' => 'form-control',
                                                     'placeholder' => '-',
                                                     ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$rec->orgid}}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-1 col-md-11">
                                            <label for="name">Сотрудник:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('staffid', $rec->staffs, $rec->staffid,
                                                     [
                                                     'id' => 'staffid',
                                                     'class' => 'form-control',
                                                     'placeholder' => '-',
                                                     ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$rec->orgstaff->lname}}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="offset-md-1 col-md-11 form-group">
                                            <label for="name">Основание (приказ, доверенность):</label>
                                            <input type="text" class="form-control" name="reason"
                                                   {{$inputReadOnly}}
                                                   value="{{old('reason',$rec->reason)}}"/>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    @if(1==0)
                                        <hr>
                                        <div class="form-group  col-md-3">
                                            <label for="active" style="color: rgb(73, 80, 87);">Действующий:
                                                {!! Form::checkbox('active', 1, $rec->active==1,
                                                     [
                                                     'class' => 'form-control',
                                                     ]) !!}</label>
                                        </div>
                                    @endif
                                    @if(1==0)
                                        <div class="form-group offset-md-9 col-md-3">
                                            <label for="active" style="color: rgb(73, 80, 87);">Очередность:</label>
                                            <input type="number" class="form-control text-right"
                                                   name="ordr"
                                                   {{$inputReadOnly}}
                                                   min="0" step="1"
                                                   value="{{$rec->ordr}}"/>
                                        </div>
                                    @endif
                                </div>

                                <hr size="1">

                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success"
                                            title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif

                                <a class="btn btn-close btn-info" href="{{ $retURL }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>

                                @if ($usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif

                                @include('layouts._who_when')
                            </div>
                        </form>
                        &nbsp;
                    </div>
                </div>

                @if($rec->id<>-1)
                    <div class="col-md-6 col-sm-12">
                        {{--                        @include('obj_staffs._usrsysrights')--}}
                    </div>
                @endif
            </div>

        </div>
    @endif
    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
    <script src="{{ asset('js/obj_staffs_edit.js') }}" defer></script>
@endsection
