@extends('layouts.edit')

@section('content')
    @if (!isset( $rec ))
        <?php
        redirect()->route('users.index');
        header("Location:" . route('users.index'));
        die();
        ?>
    @else
        <?php
        $sysobjid = 3;
        $ThisTitle = "Пользователь";

        $route_index = route('users.index') . "?page=" . session('pageno') . '#' . $rec->id;
        ?>
        {{--dd(get_defined_vars())--}}
        <style>
            .sysobjname {
                background-color: #d2f2fa;
                padding-left: 8px;
            }

            .rights_list ul li {
                font-size: 0.8em;
            }

            label {
                color: gray;
                margin-bottom: 0px;
            }

        </style>
        <div class="container">


            <div class="row ">
                <div class="col-md-6">

                    @include('layouts.edit_msgs')

                    <div class="card mt-3">
                        <div class="card-header">
                            Пользователь
                        </div>
                        <div class="card-body">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('users.update', $rec->id) }}">

                                @method('PUT')
                                @csrf

                                <div class="form-group">
                                    <label for="mname">Имя, указанное при регистрации:</label>
                                    {{ $rec->name }}
                                </div>

                                <div class="row">
                                    <div class="col-md-5 p-1">
                                        <div class="form-group">
                                            <label for="lname">Фамилия:</label>
                                            <input type="text" class="form-control" name="lname"
                                                   value="{{ $rec->lname }}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-3 p-1">
                                        <div class="form-group">
                                            <label for="fname">Имя:</label>
                                            <input type="text" class="form-control" name="fname"
                                                   value="{{ $rec->fname }}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-4 p-1">
                                        <div class="form-group">
                                            <label for="mname">Отчество:</label>
                                            <input type="text" class="form-control" name="mname"
                                                   value="{{ $rec->mname }}"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="offset-md-3 col-md-5">
                                        <div class="form-group">
                                            <label for="email">Email:</label>
                                            <div class="mt-1"><b>{{$rec->email}}</b></div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="email">Телефон:</label>
                                            <input type="text" class="form-control" name="phone"
                                                   value="{{ $rec->phone }}"/>
                                        </div>
                                    </div>

                                    @if(1==0)
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="email">MyChat UIN:</label>
                                                <input type="number" class="form-control text-center"
                                                       title="Идентификатор пользователя в системе обмена сообщениями MyChat"
                                                       name="mychat_uin"
                                                       min="1"
                                                       value="{{ $rec->mychat_user->uin }}"/>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="row">
                                    <div class="offset-md-4 col-md-5">
                                        <div class="form-group">
                                            <label for="email">Дата рождения:</label>
                                            <input type="date" class="form-control" name="birthdate"
                                                   value="{{ $rec->birthdate }}"/>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="email">Пол:</label>
                                            {!! Form::select('sex', ['M'=>'Муж','F'=>'Жен'], $rec->sex,
                                             [
                                                 'id' => 'sex',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}
                                        </div>
                                    </div>
                                </div>

                                @if (isset($rec->note))
                                    <div class="form-group">
                                        <label for="email">Примечания при регистрации:</label>
                                        {{$rec->note}}
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label for="active" style="color: rgb(73, 80, 87);">Действующий
                                        пользователь:</label>
                                    {!! Form::checkbox('active', 1, $rec->active==1,['class'=>'form-control']) !!}
                                </div>


                                <hr size="1">
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                <a class="btn btn-close btn-info" href="{{ $route_index }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 )

                                    @if ($usrrights['delete'])
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                style="margin-left:24px"
                                                formaction="{{ route('users.delete', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                                title="Удалить запись о пользователе"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif

                                    @if ($usrrights['reset.password'])
                                        <button type="submit"
                                                class="btn btn-warning"
                                                style="margin-left:24px"
                                                formaction="{{ route('users.resetpassword', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите установить пароль по-умолчанию?')"
                                                title="Сбросить пароль пользователя на 123456"
                                        >
                                            <i class="fa fa-eraser" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                @endif
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">

                    @include('users.user_orgs')

                    @include('users.user_extids')

                    @include('users._acs')

                    @include('users._usrsysrights')

                    @include('users._user_roles')

                </div>
    @endif
@endsection
