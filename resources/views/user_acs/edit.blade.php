@extends('layouts.edit')

@section('content')
    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

    <script src="{{ asset('js/ri_ac_user_acs.js') }}" defer></script>
    <script src="{{ asset('js/callListOrgs.js') }}" defer></script>

    @if (!isset( $rec ))
        <?php
        redirect()->route('users.index');
        header("Location:" . route('users.index'));
        die();
        ?>
    @else
        <?php
        $sysobjid = 1502;
        $ThisTitle = "Доступная категория информации";
        $retRoute = route('users.edit', $rec->userid);


        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        ?>
        {{--dd(get_defined_vars())--}}
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .org-aux {
                width: 100%
            }

            .btn {
                margin-bottom: 4px;
            }

            .ui-menu-item .ui-menu-item-wrapper:hover {
                /*border: none !important;*/
                border: 1px solid snow;
                color: #222222;
                background-color: lightyellow;
            }


        </style>
        <div class="container">

            <div class="row">
                <div class="col-md-7 col-sm-12">

                    @include('layouts.edit_msgs')

                    <div class="card mt-3">

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route('user_acs.update', $rec->id) }}">

                            @method('PUT')
                            @csrf
                            {{ Form::hidden('userid', $rec->userid) }}
                            <div class="card-header">
                                {{$ThisTitle}}

                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label for="name">Пользователь:</label>
                                    <div class="mb-3 ">
                                        {{$rec->user->name}}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="name">Категория информации:</label>
                                    <div class="input-group mb-3 ">
                                        @if ($usrrights['save'])
                                            {!! Form::select('acsid', $rec->acs??[], $rec->acsid,
                                             [
                                             'id' => 'acsid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}

                                        @else
                                            {{ Form::hidden('acsid', $rec->orgid) }}
                                            <input type="text" name="orgname"
                                                   class="form-control" readonly
                                                   value="{{$rec->ac->name}}"
                                            />
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="active">Действует:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1, ['class="form-control"']) !!}
                                        </div>
                                    </div>
                                </div>


                                <hr size="1">
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success"
                                            title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif

                                <a class="btn btn-close btn-info" href="{{ $retRoute }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>

                                @if ($usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route('user_acs.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif

                            </div>
                            @if ($rec->id != -1)
                                <div class="card-footer small" style="color: gray; margin:8px;">
                                    создана: {{$rec->created_at}} / {{$rec->whocrt->name}} &nbsp;
                                    изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}} &nbsp;
                                    <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="col-md-5 col-sm-12">

                    <?php $FlagsHeader = "Состояние " ?>
                    @include('objflags/objflags')

                </div>

            </div>

        </div>

@endsection
@endif
