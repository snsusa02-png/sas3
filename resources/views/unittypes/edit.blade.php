@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('unittypes.index');
        header("Location:" . route('unittypes.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>
        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

        <script src="{{ asset('js/callListOrgs.js') }}" defer></script>

        <?php
        $sysobjid = 301;
        $thisSysObjId = 301;
        $thisObjCode = 'unittypes';
        $ThisTitle = "Единица измерения: " . ($rec->name ?? '-новая-');

        $retURL = \Request::get('returl') ?? $rec->retURL ?? (route('unittypes.index') . "?page=" . session($thisObjCode . '_pageno') . '#' . $rec->id);

        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }
        </style>
        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-6">
                    <div class="card p-2 my-2 my-md-3">
                        <div class="card-header">
							<span class="font-weight-bold"
                                  style="max-width: 60%; overflow:hidden;"> {{$ThisTitle}}</span>

                            <span class="float-right">

							<a class="btn btn-close btn-light btn-sm ml-1"
                               href="{{ $retURL }}"
                               title="Вернуться в список">
								<i class="fa fa-times" aria-hidden="true"></i>
							</a>
							</span>
                        </div>
                        <div class="card-body" style="background-color: #f4f4f4">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div><br/>
                            @endif

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($thisObjCode.'.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('id', $rec->id,['id'=>'id']) }}
                                {{ Form::hidden('parent_by', $rec->parent_by) }}
                                {{ Form::hidden('retURL', $rec->retURL) }}
                                {{ Form::hidden('ttt', 1) }}

                                <div class="row">
                                    <div class="form-group col-md-4 col-sm-6 ">
                                        <label for="docnum">Название:</label>
                                        <input type="text" class="form-control text-left" name="name"
                                               value="{{old('name',$rec->name)}}"
                                               maxlength="16"/>
                                    </div>

                                    <div class="form-group col-md-8 col-sm-6 ">
                                        <label for="docnum">Описание:</label>
                                        <input type="text" class="form-control text-center" maxlength="60"
                                               name="descript"
                                               value="{{old('descript',$rec->descript)}}"/>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-4">
                                        @if(isset($rec->parent_by))
                                            <label for="plnqty">"К" перевода в <b>{{$rec->parent->name}}</b>:</label>
                                            <input type="number" class="form-control text-right "
                                                   name="k2prnt_unit"
                                                   min="0.00001" step="0.00001"
                                                   value="{{old('k2prnt_unit',$rec->k2prnt_unit)}}"/>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="plnqty">Точность, знаков:</label>
                                        <input type="number" class="form-control text-right "
                                               name="decimal_dgts"
                                               min="0" step="1" max="3"
                                               value="{{old('decimal_dgts',$rec->decimal_dgts)}}"/>
                                    </div>

                                    <div class="form-group offset-md-2 col-md-2">
                                        <div class="form-group">
                                            <label for="active">Доступно:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1, ['class="form-control"']) !!}
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $retURL }}"
                                   title="Вернуться в список">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($thisObjCode.'.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                &nbsp;
                                @if ($rec->id != -1)
                                    <div class="small" style="margin-top: 8px; color:gray;">
                                        создана: {{$rec->created_at}} / {{$rec->whocrt->name}},
                                        изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}}
                                        <br><a
                                            href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>

                @if (1==1 and $rec->id != -1)
                    <div class="col-md-6">

                        @include('objfiles/obj_files')
                        @includeif($thisObjCode.'/_childs')
                        @include('obj_names._names')
                        @include('obj_readers/_readers')

                    </div>
                @endif

            </div>
        </div>
        {{--        <script src="{{ asset('js/unittype_edit.js') }}" defer></script>--}}
    @endif
@endsection
