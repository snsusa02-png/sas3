@extends('layouts.edit')

@section('content')

    @if (!isset( $rec ))
        <?php
        redirect()->route('orgs.index');
        header("Location:" . route('orgs.index'));
        die();
        ?>
    @else
        <?php
        $sysobjid = 118;
        $sysobjcode = 'orgdeps';
        $ThisTitle = "Подразделение организации";
        $retRoute = route('orgs.edit', $rec->orgid);

        $retRoute = ($rec->retURL)
            ? ($rec->retURL . '#orgdeps')
            : route('orgs.edit', $rec->orgid) . '?#orgdeps';

        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        ?>
        {{--dd(get_defined_vars())--}}
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .org-aux {
                width: 100%
            }

            .btn {
                margin-bottom: 4px;
            }

            .ui-menu-item .ui-menu-item-wrapper:hover {
                /*border: none !important;*/
                border: 1px solid snow;
                color: #222222;
                background-color: lightyellow;
            }


        </style>
        <div class="container">

            <div class="row">
                <div class="col-md-7 col-sm-12">

                    @include('layouts.edit_msgs')

                    <div class="card mt-3">

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', $rec->id) }}">

                            @method('PUT')
                            @csrf
                            {{ Form::hidden('orgid', $rec->orgid) }}
                            {{ Form::hidden('returl', $rec->returl) }}
                            <div class="card-header">
                                {{$ThisTitle}}

                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">

                                @include('layouts.err_msgs')

                                <div class="form-group">
                                    <label for="name">Организация:</label>
                                    <div class="mb-3 ">
                                        {{$rec->org->name}}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="name" class="required">Подразделение:</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" class="form-control" name="name"
                                                   id="name" required
                                                   value="{{old('name',$rec->name) }}"
                                            />
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-2">
                                        <label for="name">Код:</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" class="form-control" name="code"
                                                   id="code" maxlength="10"
                                                   value="{{old('code',$rec->code) }}"
                                            />
                                        </div>
                                    </div>
                                    <div class="form-group offset-md-3 col-md-7">
                                        <label for="name">Руководитель подразделения:</label>
                                        <div class="input-group mb-3 ">
                                            <input type="text" class="form-control" name="mngr_name"
                                                   id="mngr_name"
                                                   value="{{old('mngr_name',$rec->mngr_name) }}"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="offset-md-0 col-md-3">
                                        <div class="form-group">
                                            <label for="active">Действующее:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1, ['class="form-control"']) !!}
                                        </div>
                                    </div>

                                    <div class="form-group offset-md-5 col-md-4">
                                        <label for="name">Порядок вывода (1-999):</label>
                                        <input type="text" class="form-control text-right" name="ordr"
                                               value="{{ old('ordr',$rec->ordr )}}"/>
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

                                <a class="btn btn-close btn-info" href="{{ $retRoute }}">
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

                            </div>
                            @if ($rec->id != -1)
                                <div class="card-footer small" style="color: gray; margin:8px;">
                                    создана: {{$rec->created_at}} / {{$rec->whocrt->name}} &nbsp;
                                    изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}} &nbsp;
                                    <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="col-md-5 col-sm-12">

                    @include('objfiles.obj_files')
                    @include('orgdeps._posts')
                    @include('orgdeps._staff')

                </div>
            </div>


        </div>
    @endif
@endsection
