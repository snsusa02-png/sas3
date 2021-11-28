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

        $route_index = route('acslst.index', $rec->sysobjid) . "?page=" . session('pageno') . '#' . $rec->userid;
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
                <div class="col-md-8">

                    @include('layouts.edit_msgs')

                    <div class="card mt-3">
                        <div class="card-header">
                            Пользователь
                        </div>
                        <div class="card-body">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('acslst.update', ['sysobjid'=>$rec->sysobjid ,'usrid'=>$rec->userid]) }}">

                                @method('PUT')
                                @csrf
                                <input type="hidden" name="sysobjid" value="{{$rec->sysobjid}}">
                                <input type="hidden" name="id" value="{{$rec->userid}}">

                                <div class="row">

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="userid">Система:</label>
                                            {{$rec->sysobj->name}}
                                        </div>
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="userid">Пользователь:</label>
                                            @if($rec->userid==-1)
                                                {!! Form::select('userid', $rec->users, $rec->userid,
                                                 [
                                                     'id' => 'userid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '',
                                                 ]) !!}
                                            @else
                                                <input type="hidden" name="userid" value="{{$rec->userid}}">
                                                {{$rec->user->name}}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if($rec->userid!=-1)

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="email">Права:</label>
                                                <style>
                                                    li {
                                                        list-style-type: none; /* Убираем маркеры */
                                                    }

                                                    ul {
                                                        margin-left: 20px; /* Отступ слева в браузере IE и Opera */
                                                        padding-left: 0; /* Отступ слева в браузере Firefox, Safari, Chrome */
                                                    }
                                                </style>
                                                <ul>
                                                    @foreach($rec->rights as $right)
                                                        <?php
                                                        $checked = (isset($right->begdt)) ? 'checked' : '';
                                                        ?>
                                                        <li><label><input type="checkbox" name="rightid[]"
                                                                          value="{{$right->id}}" {{$checked}}>
                                                                {{$right->code}} - {{$right->name}}
                                                                <span class="ml-2 small">{{$right->begdt}}  {{$right->created_by_name}}</span>
                                                                @if(isset($right->limsysobjid))
                                                                    <div class="ml-5"><span class="small">Ограничено инф. объектом:</span> {{$right->lso_name}}
                                                                        :
                                                                        @if(isset($right->lso_code))
                                                                            <a href="{{route(strtolower($right->lso_code).'.edit',$right->limobjid)}}"
                                                                               target="_blank">{{$right->limobjid}}</a>
                                                                        @else
                                                                            {{$right->limobjid}}
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{--                                <div class="form-group">--}}
                                {{--                                    <label for="active" style="color: rgb(73, 80, 87);">Действующий--}}
                                {{--                                        пользователь:</label>--}}
                                {{--                                    {!! Form::checkbox('active', 1, $rec->active==1,['class'=>'form-control']) !!}--}}
                                {{--                                </div>--}}


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

                                @endif
                                @if (1==0 and $rec->id != -1)
                                    <div class="small" style="margin-top: 8px; color: gray;">{{$rec->id}}///
                                        создан: {{$rec->created_at}} / {{$rec->whocrt->name}} &nbsp;
                                        изменен: {{$rec->updated_at}} / {{$rec->whoupd->name}} &nbsp;
                                        <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>

                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">

                    {{--                    @include('users._usrsysrights')--}}

                </div>
    @endif
@endsection
