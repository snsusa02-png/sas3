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
        $sysobjid = 963;
        $sysobjcode = 'task_users';
        $thisTitle = "Участник";

        $retRoute = "/";
        $retURL = route('tasks.edit', $rec->taskid);

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
                <div class="col-md-6 col-sm-12">

                    <div class="card mt-3">

                        @include('layouts.edit_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', [$rec->id]) }}">

                            @method('PUT')
                            @csrf
                            {!! Form::hidden('retURL', $retURL) !!}
                            {!! Form::hidden('taskid', $rec->taskid) !!}

                            <div class="card-header">
                                {{$thisTitle}}

                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right;"
                                   href="{{ $retURL }}"
                                   title="Вернуться к списку">
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
                                    <label for="taskid">Для задачи:</label>
                                    "<b>{{$rec->task->name}}"</b>
                                </div>


                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="name">Пользователь:</label>
                                        @if ($usrrights['save'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" id="username" name="username[]"
                                                       class="username form-control"
                                                       value="{{$rec->user->name}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3;" readonly>
                                                <input type="hidden" name="userid[]" class="userid" id="userid"
                                                       value="{{$rec->userid}}">
                                                <div style="width:140px">
                                                    {!! Form::select('roletypeid[]', $rec->roletypes??[], $rec->roletypeid,
                                                         [
                                                         'class' => 'form-control',
                                                         'title' => 'Роль участника',
                                                         'placeholder' => '- Роль участника -',
                                                        ]) !!}
                                                </div>

                                            </div>
                                            @if($rec->id==-1)
                                                @for ($i = 0; $i < 3; $i++)
                                                    <div class="input-group mb-3 ">
                                                        <input type="text" name="username[]"
                                                               class="username form-control"
                                                               value="{{$rec->username}}">
                                                        <input type="text"
                                                               class="form-control text-center small ac_status"
                                                               style="display: none; border: #d7f3e3; " readonly>
                                                        <input type="hidden" name="userid[]" class="userid"
                                                               value="{{$rec->userid}}">
                                                        <div style="width:140px">
                                                            {!! Form::select('roletypeid[]', [1=>'исполнитель',2=>'куратор'], $rec->roletypeid,
                                                                 [
                                                                 'class' => 'form-control',
                                                                 'title' => 'Роль участника',
                                                                ]) !!}
                                                        </div>
                                                    </div>
                                                @endfor
                                            @endif
                                        @else
                                            <div class="font-weight-bold">{{$rec->user->name}}</div>
                                        @endif
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

                                @if ($rec->id != -1)
                                    <div class="small" style="color: gray; margin:8px;">
                                        создана: {{$rec->created_at}} / {{$rec->whocrt->name}} &nbsp;
                                        изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}} &nbsp;
                                        <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                    </div>
                                @endif
                            </div>
                        </form>
                        &nbsp;
                    </div>
                </div>

                <div class="col-md-6 col-sm-12">

                </div>
            </div>

        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/callListStaff.js') }}" defer></script>
        <script src="{{ asset('js/obj_readers_edit.js') }}" defer></script>
    @endif
@endsection
