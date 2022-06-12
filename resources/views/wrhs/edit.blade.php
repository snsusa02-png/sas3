@extends('layouts.edit')

@section('content')
    @guest
        <?php

        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();


        ?>
    @else
        @if (!isset( $rec ))
            <?php
            redirect()->route('wrhs.index');
            header("Location:" . route('wrhs.index'));
            die();
            ?>
        @else
            <?php
            if (!$usrrights['read']) {
                redirect()->route('admin');
                header("Location:" . route('admin'));
                die();
            }

            $sysobjid = 202;
            $thisSysObjCode = 'wrhs';
            $thisTitle = "Склад";

            $retURL = \Request::get('returl') ?? $rec->retURL ?? (route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id);
            ?>
            <style>

                label {
                    color: gray;
                    margin-bottom: 0px;
                }

                .org-aux {
                    width: 100%
                }
            </style>
            <div class="container">
                @include('layouts.edit_msgs')

                <div class="row ">
                    <div class="col-md-7">
                        <div class="card mt-1">
                            <div class="card-header">
                                {{$thisTitle}}

                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right;"
                                   href="{{ $retURL }}"
                                   title="Вернуться в список ">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>

                            <div class="card-body">
                                @include('layouts.err_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route($thisSysObjCode.'.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    <div class="form-group">
                                        <label for="name">Название:</label>
                                        <input type="text" class="form-control" name="name" value="{{$rec->name}}"/>
                                    </div>

                                    <div class="form-group">
                                        <label for="address">Адрес:</label>
                                        <input type="text" class="form-control" name="address"
                                               value="{{$rec->address}}"/>
                                    </div>

                                    <div class="form-group">
                                        <label for="descript">Описание:</label>
                                        <textarea class="form-control rounded-0" name="descript" id="descript"
                                                  rows="3">{{$rec->descript}}</textarea>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="active" style="color: rgb(73, 80, 87);">Доступен:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1) !!}
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="forsale" style="color: rgb(73, 80, 87);">Отпуск товаров для
                                                клиентов:</label>
                                            {!! Form::checkbox('forsale', 1, $rec->forsale==1) !!}
                                        </div>
                                    </div>


                                    <hr size="1">
                                    @if ($usrrights['save'])
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            Сохранить
                                        </button>
                                    @endif
                                    <a class="btn btn-close btn-info" href="{{ route($thisSysObjCode.'.search') }}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>

                                    @if ($usrrights['delete'] )
                                        <button type="submit"
                                                class="btn btn-danger"
                                                style="margin-left:24px"
                                                formaction="{{ route($thisSysObjCode.'.delete', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                                title="Удалить"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                    @if($rec->id<>-1)
                                        <div class="small" style="margin-top: 8px;">
                                            создана: {{$rec->created_at}} / {{$rec->whocrt->name}}
                                            &nbsp;&nbsp;
                                            изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}}
                                            <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-5">

                        @include('wrhs._buildobjs')
                        @include('wrhs._boxes')
                        @include('wrhs._stocks')
                        {{--                        @includeIf('wrhs._auxinfo')--}}

                    </div>

                </div>
            </div>
        @endif
    @endguest
@endsection
