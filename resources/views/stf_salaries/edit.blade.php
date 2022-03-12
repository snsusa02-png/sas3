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
            $thisSysObjId = 1208;
            $sysobjid = $thisSysObjId;
            $sysobjcode = 'stf_salaries';
            $thisTitle = "Данные о начислении заработной платы";

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
                                      action="{{ route($sysobjcode.'.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    {{ Form::hidden('staffid', $rec->staffid) }}
                                    {{ Form::hidden('id', $rec->id,['id'=>'id']) }}

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="">Для:</label>
                                            <input type="text" class="form-control"
                                                   readonly
                                                   value="{{ $rec->_obj_info }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-8">
                                            <label for="name" class="required">Период работы:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group">
                                                    <input type="date" class="form-control text-center font-weight-bold"
                                                           name="wrkbegdate" id="wrkbegdate" required
                                                           min="{{$rec->wrkdate_min}}"
                                                           max="{{today()->format('Y-m-d')}}"
                                                           value="{{old('wrkbegdate',$rec->wrkbegdate)}}"/>
                                                    &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;
                                                    <input type="date" class="form-control text-center font-weight-bold"
                                                           name="wrkenddate" id="wrkenddate" required
                                                           min="{{$rec->wrkdate_max}}"
                                                           max="{{today()->format('Y-m-d')}}"
                                                           value="{{old('wrkenddate',$rec->wrkenddate)}}"/>
                                                </div>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">
                                                    {{date_create($rec->wrkbegdate)->format('d.m.Y')}} -
                                                    {{date_create($rec->wrkenddate)->format('d.m.Y')}}
                                                    {{ Form::hidden('wrkbegdate', $rec->wrkbegdate,['id'=>'wrkbegdate']) }}
                                                    {{ Form::hidden('wrkenddate', $rec->wrkenddate,['id'=>'wrkenddate']) }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="zip" class="required">Начислено, &#8381;:</label>
                                            @if ($usrrights['edit'])
                                                <input type="number" class="form-control font-weight-bold text-right"
                                                       name="salary_sum"
                                                       value="{{ old('salary_sum',$rec->salary_sum) }}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-right">
                                                    {{number_format($rec->salary_sum,2)}}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{--                                    <div class="row">--}}
                                    {{--                                        <div class="form-group offset-md-0 col-md-12">--}}
                                    {{--                                            <label for="name" class="">Примечание:</label>--}}
                                    {{--                                            <input type="text" class="form-control" name="notes" maxlength="160"--}}
                                    {{--                                                   value="{{ old('notes',$rec->notes) }}"/>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}


                                    <div class="row">
                                        <div class="form-group offset-md-10 col-md-2">
                                            <label for="active" style="color:rgb(73, 80, 87);">Факт:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1,['class'=>'form-control']) !!}
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
                                    &nbsp;
                                    @if ($usrrights['delete'])
                                        <a class="btn btn-danger btn-sm"
                                           style="margin-left:24px"
                                           href="{{ route($sysobjcode.'.delete', $rec->id)}}"

                                           onclick="return confirm('Вы действительно хотите удалить запись?')"
                                           title="Удалить запись"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                </form>

                                @include('layouts._who_when')
                            </div>

                        </div>
                    </div>

                    @if($rec->id<>-1)
                        <div class="col-md-5">
                        </div>
                    @endif

                </div>
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"
                    integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
            <script src="{{ asset('js/stf_salary_edit.js') }}" defer></script>

        @endif
    @endguest
@endsection
