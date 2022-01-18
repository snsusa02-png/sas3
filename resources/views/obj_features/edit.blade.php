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
            $thisSysObjId = 1793;
            $sysobjid = $thisSysObjId;
            $sysobjcode = 'obj_features';
            $thisTitle = "Характеристика / Особенность";

            $retRoute = $rec->retURL;

            //для блокировки текстовых полей пользователям, не имеющим право на редактирование
            $inputReadOnly = "readonly";
            if ($usrrights['save']) $inputReadOnly = "";
            ?>

            <div class="container">

                @include('layouts.edit_msgs')

                <div class="row ">

                    <div class="col-md-8">
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
                                    {{ Form::hidden('sysobjid', $rec->sysobjid) }}
                                    {{ Form::hidden('objid', $rec->objid) }}


                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="">Для:</label>
                                            <input type="text" class="form-control"
                                                   readonly
                                                   value="{{ $rec->_obj_info }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="featuretypeid" class="required">Тип характеристики:</label>
                                            {!! Form::select('featuretypeid',  $rec->featuretypes??[], $rec->featuretypeid??null,
                                             [
                                             'id' => 'featuretypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             'required' => 'required',
                                             ]) !!}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group offset-md-2 col-md-8">
                                            <label for="name" class="required">Значение характеристики:</label>
                                            <input type="text" class="form-control text-center font-weight-bold"
                                                   name="val" maxlength="60"
                                                   required
                                                   value="{{ old('val',$rec->val) }}"/>
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
                                                formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
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
        @endif
    @endguest
@endsection
