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
                $thisSysObjId = 923;
                $sysobjid = $thisSysObjId;
                $sysobjcode = 'obj_docs';
                $thisTitle = "Документ";

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
                                    {!! Form::hidden('retURL', $retURL) !!}
                                    {{ Form::hidden('sysobjid', $rec->sysobjid) }}
                                    {{ Form::hidden('objid', $rec->objid) }}


                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="">Для: {{$rec->_sysobj_name}}</label>
                                            <input type="text" class="form-control"
                                                   readonly
                                                   value="{{ $rec->_obj_info }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="doctypeid" class="required">Тип документа:</label>
                                            {!! Form::select('doctypeid',  $rec->doctypes??[], $rec->doctypeid??null,
                                             [
                                             'id' => 'doctypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             'required' => 'required',
                                             ]) !!}
                                        </div>

                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="" id="lbl_raid_qty">Номер документа:</label>
                                            @if ($usrrights['save'])
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="docnum" id="docnum"
                                                           class="form-control text-left font-weight-bold"
                                                           value="{{old('docnum',$rec->docnum)}}">
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->docnum}}</div>
                                            @endif
                                        </div>
                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="" id="lbl_raid_qty">Серия:</label>
                                            @if ($usrrights['save'])
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="docseria" id="docseria"
                                                           class="form-control text-left font-weight-bold"
                                                           value="{{old('docseria',$rec->docseria)}}">
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->docseria}}</div>
                                            @endif
                                        </div>

                                    </div>
                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="required">Дата документа:</label>
                                            @if ($usrrights['save'])
                                                <input type="date" class="form-control text-center font-weight-bold required"
                                                       name="docdate" id="docdate" required
                                                       min="{{$rec->docdate_min}}"
                                                       max="{{today()->format('Y-m-d')}}"
                                                       value="{{old('docdate',$rec->docdate)}}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{date_create($rec->docdate)->format('d.m.Y')}}
                                                    {{ Form::hidden('docdate', $rec->docdate,['id'=>'docdate']) }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-1 col-md-3">
                                            <label for="name" class="required">Начало действия:</label>
                                            @if ($usrrights['save'])
                                                <input type="date" class="form-control text-center font-weight-bold required"
                                                       name="begdate" id="begdate" required
                                                       value="{{old('begdate',$rec->begdate)}}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{date_create($rec->begdate)->format('d.m.Y')}}
                                                    {{ Form::hidden('begdate', $rec->begdate,['id'=>'begdate']) }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="required0">Окончание:</label>
                                            @if ($usrrights['save'])
                                                <input type="date" class="form-control text-center font-weight-bold "
                                                       name="enddate" id="enddate"
                                                       value="{{old('enddate',$rec->enddate)}}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{date_create($rec->begdate)->format('d.m.Y')}}
                                                    {{ Form::hidden('enddate', $rec->enddate,['id'=>'enddate']) }}
                                                </div>
                                            @endif
                                        </div>

                                    </div>


                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="required0">Примечание:</label>
                                            <input type="text" class="form-control" name="notes" maxlength="160"
                                                   value="{{ old('notes',$rec->notes) }}"/>
                                        </div>


                                    </div>

                                    <div class="form-group">
                                        <label for="active" style="color:rgb(73, 80, 87);">актуально:</label>
                                        {!! Form::checkbox('active', 1, $rec->active==1) !!}
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
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                style="margin-left:24px"
                                                formaction="{{ route($sysobjcode.'.delete', $rec->id)}}?returl={{$retRoute}}"
                                                formmethod="post"
                                                title="Удалить"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
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

            <script src="{{ asset('js/obj_doc_edit.js') }}" defer></script>
        @endif
    @endguest
@endsection
