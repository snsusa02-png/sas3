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
        @if (!isset( $rec))
            <?php
            redirect()->route('orgstaff.index');
            header("Location:" . route('orgstaff.index'));
            die();
            ?>
        @else

            <?php
            $sysobjid = 121;
            $thisSysObjCode = 'orgstaff';
            $orgid = $rec->orgid;

            $retURL = \Request::get('returl') ?? $rec->retURL ?? (route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id);

            ?>
            <style>
                label {
                    color: gray;
                    margin-bottom: 0px;
                }

                .photo {
                    display: block;
                    max-width: 120px;
                    max-height: 160px;
                    width: auto;
                    height: auto;
                    margin: auto;
                }
            </style>

            <div class="container">

                <div class="row ">
                    <div class="col-md-6">
                        <div class="card mt-3">
                            <div class="card-header">
                                Категория информации
                                <a class="btn btn-close btn-info btn-sm"
                                   style="float:right;"
                                   href="{{ $retURL }}"
                                   title="Вернуться в список ">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">

                                @include('layouts.edit_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route('acs.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    {!! Form::hidden('id', $rec->id,['id'=>'id']) !!}
                                    {!! Form::hidden('returl', $retURL) !!}

                                    @include('layouts.err_msgs')

                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="orgid">Название категории:</label>
                                            <div>
                                                <input type="text" name="name" value="{{$rec->name}}"
                                                       maxlength="60" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="offset-md-0 col-md-12">
                                            <div class="form-group">
                                                <label for="email">Описание:</label>
                                                <textarea class="form-control rounded-0" name="descript"
                                                          id="descript" maxlength="360"
                                                          rows="3">{{$rec->descript}}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-10 col-md-2">
                                            <div class="form-group">
                                                <label for="active">Доступно:</label>
                                                {!! Form::checkbox('active', 1, $rec->active==1, ['class="form-control"']) !!}
                                            </div>
                                        </div>
                                    </div>


                                    <hr size="1">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                    &nbsp;
                                    <a class="btn btn-close btn-info"
                                       href="{{ $retURL }}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>
                                    &nbsp;
                                    @if ($rec->id != -1)
                                        <button type="submit"
                                                class="btn btn-danger"
                                                style="margin-left:24px"
                                                formaction="{{ route('orgstaff.del', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                                title="Удалить"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                    <div class="small" style="margin-top: 8px; color:gray;">
                                        создана: {{$rec->created_at}} / {{$rec->whocrt->name}}
                                        &nbsp;&nbsp;
                                        изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}}
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>

                    @if (1==1 and $rec->id != -1)
                        <div class="col-md-6">
                            @include('acs._owners')
                        </div>
                    @endif

                </div>
            </div>
            <script src="{{ asset('js/acs_edit.js') }}" defer></script>

        @endif
    @endguest
@endsection
