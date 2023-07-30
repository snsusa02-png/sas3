@extends('layouts.edit')

@section('content')
    @if (!isset( $rec ))
        <?php
        redirect()->route($sysobjcode . '.index');
        header("Location:" . route($sysobjcode . '.index'));
        die();
        ?>
    @else
        <?php
        $sysobjid = 1551;
        $sysobjcode = 'acl_roles';
        $thisTitle = "Роль доступа";

        $retRoute = route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;

        $route_index = route($sysobjcode . '.index') . "?page=" . session('pageno') . '#' . $rec->id;
        ?>
        <style>

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
                            {{$thisTitle}}
                        </div>
                        <div class="card-body">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($sysobjcode . '.update', $rec->id) }}">

                                @method('PUT')
                                @csrf

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="name">Название роли:</label>
                                            <input type="text" class="form-control" name="name"
                                                   value="{{ $rec->name }}"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="offset-md-0 col-md-12">
                                        <div class="form-group">
                                            <label for="email">Описание:</label>
                                            <textarea name="descript" class="form-control">{{$rec->descript}}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="active" style="color: rgb(73, 80, 87);">Действует:</label>
                                    {!! Form::checkbox('active', 1, $rec->active==1,['class'=>'form-control']) !!}
                                </div>


                                <hr size="1">
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                <a class="btn btn-close btn-info" href="{{ $retRoute }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 )

                                    @if ($usrrights['delete'])
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                style="margin-left:24px"
                                                formaction="{{ route($sysobjcode . '.delete', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                                title="Удалить запись о пользователе"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif

                                @endif
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">

                    @include($sysobjcode . '._role_rights')
                    @include($sysobjcode . '._role_users')

                </div>
    @endif
@endsection
