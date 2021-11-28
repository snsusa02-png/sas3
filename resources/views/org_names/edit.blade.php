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
            redirect()->route('doctypes.index');
            header("Location:" . route('doctypes.index'));
            die();
            ?>
        @else
            <?php
            $thisSysObjId = 1622;
            $sysobjid = 1622;
            $sysobjcode = 'org_names';
            $thisTitle = "Название организации";

            $retRoute = route('orgs.edit', $rec->orgid) . '#org_names';

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
                            </div>
                            <div class="card-body">

                                @include('layouts.err_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route($sysobjcode.'.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    {{ Form::hidden('orgid', $rec->orgid) }}


                                    <div class="form-group">
                                        <label for="name">Наименование:</label>
                                        <input type="text" class="form-control" name="name" maxlength="120"
                                               value="{{ old('name',$rec->name) }}"/>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="vactype" class="required">Тип названия:</label>
                                            {!! Form::select('nametypeid',  $rec->nametypes??[], $rec->nametypeid??null,
                                             [
                                             'id' => 'nametypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-4 col-md-4 vt1_hide">
                                            <label for="begdate">Начало действия:</label>
                                            <input type="date" class="form-control"
                                                   name="begdate" id="begdate"
                                                   value="{{ old('begdate',$rec->begdate) }}"/>
                                        </div>

                                        <div class="form-group offset-md-0 col-md-4 t10_hide t90_hide vt1_hide ">
                                            <label for="enddate">Окончание действия:</label>
                                            <input type="date" class="form-control"
                                                   name="enddate" id="enddate"
                                                   value="{{ old('enddate',$rec->enddate) }}"/>
                                        </div>

                                    </div>

                                    <div class="form-group">
                                        <label for="active" style="color:rgb(73, 80, 87);">доступно для
                                            использования:</label>
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
                                                formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
                                                formmethod="post"
                                                title="Удалить"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                </form>
                            </div>

                            @include('layouts._who_when')

                        </div>
                    </div>

                    @if($rec->id<>-1)
                        <div class="col-md-5">


                        </div>
                    @endif

                </div>
            </div>
        @endif
    @endguest
@endsection
