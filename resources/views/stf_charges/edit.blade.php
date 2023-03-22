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
            $thisSysObjId = 1212;
            $sysobjid = $thisSysObjId;
            $sysobjcode = 'stf_charges';
            $thisTitle = "Применяемые начисления/удержания";

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
                                        <div class="form-group col-md-9">
                                            <label for="chargetype_name" class="required">Тип
                                                начисления/удержания:</label>
                                            <div class="input-group">

                                                {!! Form::select('orgchargeid', $rec->orgcharges??[], $rec->orgchargeid,
                                                 [
                                                 'id' => 'orgchargeid',
                                                 'class' => 'form-control required',
                                                 'placeholder' => '',
                                                 'required' => 'required',
                                                 ]) !!}
                                                <a class="btn btn-light" id="orgchargeid_lnk"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="charge_sum" class="required">Сумма, &#8381;:</label>
                                            @if ($usrrights['edit'])
                                                <input type="number" class="form-control font-weight-bold text-right"
                                                       name="charge_sum"
                                                       value="{{ old('charge_sum',$rec->charge_sum) }}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-right">
                                                    {{number_format($rec->charge_sum,2)}}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

{{--                                    <div class="row">--}}
{{--                                        <div class="form-group offset-md-2 col-md-8">--}}
{{--                                            <label for="name" class="required">Период применения:</label>--}}
{{--                                            @if ($usrrights['edit'])--}}
{{--                                                <div class="input-group">--}}
{{--                                                    <input type="date" class="form-control text-center font-weight-bold"--}}
{{--                                                           name="begdate" id="begdate" required--}}
{{--                                                           min="{{$rec->wrkdate_min}}"--}}
{{--                                                           max="{{today()->format('Y-m-d')}}"--}}
{{--                                                           value="{{old('begdate',$rec->begdate)}}"/>--}}
{{--                                                    &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;--}}
{{--                                                    <input type="date" class="form-control text-center font-weight-bold"--}}
{{--                                                           name="enddate" id="enddate"--}}
{{--                                                           min="{{$rec->wrkdate_max}}"--}}
{{--                                                           max="{{today()->format('Y-m-d')}}"--}}
{{--                                                           value="{{old('enddate',$rec->enddate)}}"/>--}}
{{--                                                </div>--}}
{{--                                            @else--}}
{{--                                                <div--}}
{{--                                                    class="font-weight-bold text-center">--}}
{{--                                                    {{date_create($rec->begdate)->format('d.m.Y')}} ---}}
{{--                                                    {{date_create($rec->enddate)->format('d.m.Y')}}--}}
{{--                                                    {{ Form::hidden('begdate', $rec->begdate,['id'=>'begdate']) }}--}}
{{--                                                    {{ Form::hidden('enddate', $rec->enddate,['id'=>'enddate']) }}--}}
{{--                                                </div>--}}
{{--                                            @endif--}}
{{--                                        </div>--}}
{{--                                        <div class="form-group offset-md-0 col-md-2">--}}
{{--                                            <label for="active" style="color:rgb(73, 80, 87);">Действует:</label>--}}
{{--                                            {!! Form::checkbox('active', 1, $rec->active==1,['class'=>'form-control']) !!}--}}
{{--                                        </div>--}}
{{--                                    </div>--}}

{{--                                    <div class="row">--}}
{{--                                        <div class="form-group offset-md-0 col-md-12">--}}
{{--                                            <label for="name" class="">Примечание:</label>--}}
{{--                                            <input type="text" class="form-control" name="notes" maxlength="160"--}}
{{--                                                   value="{{ old('notes',$rec->notes) }}"/>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}


                                    <div class="row">
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
