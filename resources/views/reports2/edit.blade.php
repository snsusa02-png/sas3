@extends('layouts.edit')

@section('content')

    <?php
    $thisTitle = "Отчет";
    $thisSysObjCode = 'reports';
    $thisSysObjId = 855;
    $sysobjid = $thisSysObjId;

    ?>

    @if (!isset( $rec ))
        <?php
        redirect()->route($thisSysObjCode . '.index');
        header("Location:" . route($thisSysObjCode . '.index'));
        die();
        ?>
    @else
        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .btn {
                margin-bottom: 4px;
            }
        </style>

        <?php
        $route_index = route($thisSysObjCode . '.index') . "?page=" . session('pageno') . '#' . $rec->id;

        $rqstnum = ($rec->id == -1) ? '-новый-' : ($rec->name . ' №' . $rec->docnum);

        $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');



        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        ?>
        {{--dd(get_defined_vars())--}}
        <div class="container">

            @include('layouts.edit_msgs')

            <div class="row">
                <div class="col-md-7 col-sm-12" style="min-width:450px;max-width:900px;">

                    <div class="card p-2 my-2 my-md-3 ">
                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($thisSysObjCode.'.update', $rec->id) }}">

                            @method('PUT')
                            @csrf
                            <div class="card-header">
								<span data-toggle="collapse" data-target="#main" style="cursor: pointer">
				 {{$thisTitle}} <b>{{$rqstnum}}</b></span>

                                <div class="float-right">
                                    @if (1==1)
                                        <button id="btn1" type="button"
                                                class="btn btn-light btn-sm mr-1" data-toggle="collapse"
                                                data-target="#main"
                                        ><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                                    @endif
                                    <a class="btn btn-close btn-light btn-sm"
                                       style="float:right;"
                                       href="{{ $route_index }}"
                                       title="Вернуться в список">
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </a>
                                </div>

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                            </div>

                            <div class="card-body collapse {{(1==1 or ($rec->id==-1) or ($errors->any()))?' show':' '}}"
                                 id="main">

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="notes">Название:</label>
                                        @if($rec->active==0 and $usrrights['save']??false)
                                            <input type="text" class="form-control" name="name"
                                                   maxlength="160"
                                                   value="{{old('name',$rec->name)}}"/>
                                        @else
                                            <input type="text" readonly value="{{$rec->name}}"
                                                   class="form-control">
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="descript" class="">Описание:</label>
                                        @if($rec->active==0 and $usrrights['save']??false)
                                            <textarea class="form-control rounded-0"
                                                      name="descript" id="descript"
                                                      rows="4">{{ old('descript',$rec->descript) }}</textarea>
                                        @else
                                            <div class="border p-2">{{$rec->descript}}&nbsp;</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="descript">Категория доступа:</label>

                                        @if($usrrights['acs.edit'] and $usrrights['save']??false)
                                            {!! Form::select('acsid', $rec->acs, $rec->acsid,
                                                                                     [
                                                                                     'class' => 'form-control',
                                                                                     'placeholder' => '-выбор-',
                                                                                     ]) !!}
                                        @else
                                            <div class="border p-2">{{$rec->ac->name}}&nbsp;</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-6">
                                        <label for="public" class="required"
                                               style="color: rgb(73, 80, 87);">Видимость:</label>
                                        <?php
                                        $publictypes = [0 => 'ограничено списком доступа', 1 => 'доступно всем'];
                                        ?>
                                        @if ($usrrights['save'] )
                                            {!! Form::select('public', $publictypes
                                                , old('public',$rec->public),
                                                 [
                                                 'id' => 'public',
                                                 'class' => 'form-control',
                                                 'required' => 'required',
                                                 'placeholder' => '-',
                                                 ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$publictypes[$rec->public]??'?'}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="statusid" class="required">Статус:</label>
                                        {!! Form::select('active', $rec->statuses, $rec->active,
                                         [
                                         'class' => 'form-control',
                                         'placeholder' => '-выбор-',
                                         'required' => 'required',
                                         ]) !!}
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-12">
                                        <label for="public" class=""
                                               style="color: rgb(73, 80, 87);">Ограничение по наличию права:</label>
                                        <input type="hidden" name="acs_rightid" class="ac_sysfunc_id"
                                        @if ($usrrights['save'])
                                                   value="{{$rec->acs_rightid}}">
                                            <input type="text" class="form-control ac_sysfunc_name"
                                                   name="acs_right_name"
                                                   maxlength="160"
                                                   value="{{old('acs_right_name',$rec->acs_right_name)}}"/>
                                        @else
                                            <div class="font-weight-bold">{{$rec->acs_right_name}}</div>
                                        @endif
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

                                <a class="btn btn-close btn-info" href="{{ $route_index }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 )

                                    @if ($rec->active==0 and $usrrights['delete'])
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                style="margin-left:24px"
                                                formaction="{{ route($thisSysObjCode.'.delete', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                                title="Удалить запись"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif

                                    @if ($usrrights[$thisSysObjCode.'.admindelete']??false)
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                style="margin-left:24px; margin-right:8px;"
                                                formaction="{{ route($thisSysObjCode.'.admindelete', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Будет удален документ со всем содержимым!\n\nПродолжать?')"
                                                title="Удалить заказ со всем содержимым"
                                        >
                                            <i class="fa fa-bomb" aria-hidden="true"></i>
                                        </button>
                                    @endif

                                    @if ($rec->active==1)
                                        <span class="float-right">
                                        &nbsp;&nbsp;&nbsp;<a
                                                href="{{route('reports.rep'.$rec->id,['route'=>Route::current()->getName()])}}">Запросить</a>
                                        </span>
                                    @endif



                                    {{--TODO:Доделать условия для вывода печатных форм											--}}
                                    @if (1==0 and $usrrights[$thisSysObjCode.'.print']??false)
                                        <div class="row">
                                            <div class="form-group offset-md-4 col-md-8">
                                                <label for="printform">Печать:</label>
                                                {!! Form::select('printform', $printforms ,0,['class' => 'form-control small','onChange'=>'if(this.value) window.open(this.value,"Печатная форма");']) !!}
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <div class="card-footer">
                                @if ($rec->id != -1)
                                    <div class="small" style="margin-top: 8px; color: gray;">
                                        создан: {{$rec->created_at}} / {{$rec->whocrt->name}}
                                        <br>изменен: {{$rec->updated_at}} / {{$rec->whoupd->name}}
                                        <span class="float-right">
                                        &nbsp;&nbsp;&nbsp;<a
                                                href="{{route('objevntlog',['sysobjid'=>$thisSysObjId, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                        </span>
                                    </div>
                                @endif
                            </div>

                        </form>
                    </div>
                </div>

                <div class="col-md-5 col-sm-12">
                    @include('objfiles.obj_files')
                    @include('obj_readers/_readers')
                    @include('reports2/_users_stat')
                </div>

            </div>

            <script src="{{ asset('js/report_edit.js') }}" defer></script>
        </div>
    @endif

@endsection
