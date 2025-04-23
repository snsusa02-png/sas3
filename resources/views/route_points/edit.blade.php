@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('route_points.index');
        header("Location:" . route('route_points.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 1228;
        $thisSysObjId = $sysobjid;
        $thisSysObjCode = 'route_points';
        $sysobjcode = $thisSysObjCode;
        $ThisTitle = "Баллы за маршрут";

        $route_index = route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id;
        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .org-aux {
                width: 100%
            }

            .photo {
                display: block;
                max-width: 116px;
                max-height: 136px;
                width: auto;
                height: auto;
                margin: auto;
            }
        </style>
        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-7">
                    <div class="card p-2 my-2 my-md-3">
                        <div class="card-header">
                            {{$ThisTitle}}
                            <a class="btn btn-close btn-light btn-sm"
                               style="float:right;"
                               href="{{ $route_index }}"
                               title="Вернуться в список">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="card-body">
                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($thisSysObjCode . '.update', $rec->id) }}">
                                @method('PUT')
                                @csrf

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="name" class="required">Начало маршрута:</label>
                                        @if ($usrrights['save'])
                                            <input type="text" class="form-control font-weight-bold"
                                                   name="src_placename" list="places"
                                                   maxlength="60"
                                                   value="{{old('src_placename',$rec->src_placename)}}"/>
                                            <datalist id="places">
                                            @foreach($data->places as $place)
                                            <option value="{{ $place->name }}">
                                            @endforeach
                                            </datalist>
                                        @else
                                            <div class="font-weight-bold">{{$rec->tgt_placename}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="name" class="required">Окончание маршрута:</label>
                                        @if ($usrrights['save'])
                                            <input type="text" class="form-control font-weight-bold"
                                                   name="tgt_placename" list="places"
                                                   maxlength="60"
                                                   value="{{old('tgt_placename',$rec->tgt_placename)}}"/>
                                        @else
                                            <div class="font-weight-bold">{{$rec->tgt_placename}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="">Баллы:</label>
                                        @if ($usrrights['save'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="points" id="points"
                                                       class="form-control font-weight-bold "
                                                       min="0" step="0.001"
                                                       value="{{old('points',$rec->points)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->points}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-3 col-md-3">
                                        <label for="name" class="">Действует с:</label>
                                        @if ($usrrights['save'])
                                            <div class="input-group mb-3 ">
                                                <input type="date" name="begdate" id="begdate"
                                                       class="form-control font-weight-bold"
                                                       value="{{old('begdate',$rec->begdate)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->begdate}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="">по:</label>
                                            <div class="mt-2 font-weight-bold">{{$rec->enddate}}</div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="active" style="color: rgb(73, 80, 87);">Активная:</label>
                                    {!! Form::checkbox('active', 1, $rec->active==1) !!}
                                </div>

                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $route_index }}"
                                   title="Вернуться в список">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($thisSysObjCode . '.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                &nbsp;
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>

                @if (1==0 and $rec->id != -1)
                    <div class="col-md-6">

                        @include('objfiles.obj_files')

                    </div>

                @endif


            </div>
        </div>
        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/fuelcard_edit.js') }}" defer></script>
    @endif
@endsection
