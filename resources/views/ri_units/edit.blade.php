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
        $sysobjid = 144;
        $sysobjcode = 'ri_units';
        $thisTitle = "Альтернативная ЕИ";

        $retRoute = "/";
        $retURL = $rec->retRoute ?? Request::get('returl');

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
                <div class="col-md-7 col-sm-12">

                    <div class="card mt-3">

                        @include('layouts.edit_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', [$rec->id]) }}">

                            @method('PUT')
                            @csrf
                            {!! Form::hidden('retURL', $retURL) !!}
                            {!! Form::hidden('refitmid', $rec->refitmid) !!}
                            {!! Form::hidden('ttt', 1) !!}

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
                                    <label for="sysobjid">Для записи:</label>
                                    "<b>{{$rec->refitem->name}}</b>"
                                </div>


                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="name" class="required">Единица измерения:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('unittypeid', $rec->unittypes,
                                            old('unittypeid',$rec->unittypeid),
                                            ['class' => 'form-control',
                                            'placeholder'=>'',
                                            'required'=>true,
                                            ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->unittype->name}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="name" class="text-center required">
                                            Коэффициент пересчета в {{$rec->refitem->unittype->name}}:
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-append btn-sm ">
                                                1 ЕИ =
                                            </div>
                                            @if ($usrrights['save'])
                                                <input type="number" name="k2ref_unit" min="0" step="0.00000001"
                                                       class="form-control text-center"
                                                       required
                                                       value="{{old('k2ref_unit',$rec->k2ref_unit)}}">
                                            @else
                                                <div class="font-weight-bold">{{$rec->unittype->name}}</div>
                                            @endif
                                            <div class="input-group-append btn-sm ">
                                                {{$rec->refitem->unittype->name}}
                                            </div>
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
        <script src="{{ asset('js/obj_names_edit.js') }}" defer></script>
    @endif
@endsection
