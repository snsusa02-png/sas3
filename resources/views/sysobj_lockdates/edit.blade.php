@extends('layouts.edit')
@section('content')

    <style>
        label {
            color: gray;
            margin-bottom: 0px;
        }
    </style>
    @guest
        <?php
        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();
        ?>
    @else
        @if (!isset( $rec))
            <?php
            redirect()->route('home');
            header("Location:" . route('home'));
            die();
            ?>
        @else
            <?php
            $thisSysObjId = 1923;
            $sysobjid = $thisSysObjId;
            $sysobjcode = 'sysobj_lockdates';
            $thisTitle = "Дата блокировки";

            $retRoute = $rec->retURL;

            //для блокировки текстовых полей пользователям, не имеющим право на редактирование
            $inputReadOnly = "readonly";
            if ($usrrights['save']) $inputReadOnly = "";
            ?>

            <div class="container">

                @include('layouts.edit_msgs')

                <div class="row ">

                    <div class="col-md-7">
                        <div class="card mt-3">
                            <div class="card-header">
                                {{$thisTitle}}
                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">

                                @include('layouts.err_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route($sysobjcode.'.update', $rec->sysobjid) }}">
                                    @method('PUT')
                                    @csrf
                                    {!! Form::hidden('retURL', $rec->retURL) !!}

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="">Для подсистемы:</label>
                                            <input type="text" class="form-control"
                                                   readonly
                                                   value="{{ $rec->_sysobj_name }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-4 col-md-4">
                                            <label for="name" class="required">Дата блокировки:</label>
                                            @if ($usrrights['save'])
                                                <input type="date" class="form-control text-center font-weight-bold"
                                                       name="lockdate" id="lockdate" required
                                                       max="{{today()->format('Y-m-d')}}"
                                                       value="{{old('lockdate',$rec->lockdate)}}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{date_create($rec->lockdate)->format('d.m.Y')}}
                                                    {{ Form::hidden('lockdate', $rec->lockdate,['id'=>'lockdate']) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($usrrights['save'])
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            Сохранить
                                        </button>
                                    @endif
                                    &nbsp;
                                    <a class="btn btn-close btn-info" href="{{ $retRoute }}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>
                                </form>

                                {{--                                @include('layouts._who_when')--}}
                            </div>


                        </div>
                    </div>

                </div>
            </div>

        @endif
    @endguest
@endsection
