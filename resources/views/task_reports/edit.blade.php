@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('tasks.index');
        header("Location:" . route('tasks.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css"/>
        <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>


        <?php
        $sysobjid = 965;
        $thisSysObjId = $sysobjid;
        $sysobjcode = 'task_reports';
        $objcode = 'task_reports';
        $ThisTitle = "Отчет по задаче";

        $route_index = route('tasks.edit', $rec->taskid) . '#report_' . $rec->id;


        $inputReadOnly = '';
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
                max-width: 120px;
                max-height: 160px;
                width: auto;
                height: auto;
                margin: auto;
            }
        </style>
        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-6">
                    <div class="card p-2 my-2 my-md-3" style="background-color: #f8f8f8">
                        <div class="card-header">
                            {{$ThisTitle}} "<b>{{$rec->task->name}}</b>"
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
                                  action="{{ route($objcode.'.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('taskid', $rec->taskid) }}
                                {{ Form::hidden('ttt', 0) }}


                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-6">
                                        <label class="required">Период работы, с:</label>
                                        @if ($usrrights['save'])
                                            <input type="datetime-local" name="wrkbegdt" id="wrkbegdt"
                                                   class="form-control text-center" required readonly
                                                   value="{{old('wrkbegdt',$rec->wrkbegdt)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->wrkbegdt}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-6">
                                        <label class="required">Период работы, по:</label>
                                        @if ($usrrights['save'])
                                            <input type="datetime-local" name="wrkenddt" id="wrkenddt"
                                                   class="form-control text-center" required
                                                   min="{{$rec->wrkbegdt}}" max="{{$rec->max_dt}}"
                                                   value="{{old('wrkenddt',$rec->wrkenddt)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->wrkenddt}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="offset-md-0 col-md-12">
                                        <div class="form-group">
                                            <label for="report" class="required">Отчет:</label>
                                            @if ($usrrights['save'] )
                                                <textarea class="form-control rounded-0"
                                                          name="report" id="report" required
                                                          rows="6">{{old('report',$rec->report)}}</textarea>
                                            @else
                                                <div class="font-weight-bold">
                                                    <div class="font-weight-bold">{{$rec->report??'-'}}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if(1==1)
                                    <div class="row">
                                        <div class="form-group col-md-4" id="progress_div"
                                             style="">
                                            <label class="required">Исполнение, %:</label>
                                            <input type="text"
                                                   class="form-control text-center"
                                                   min="0" max="100" step="5"
                                                   placeholder="%" required
                                                   id="progress"
                                                   name="progress"
                                                   value="{{old('progress',$rec->progress)}}"
                                                   onchange="setRangeByInput(this, 'slide_progress')"
                                            />
                                            <div class="slidecontainer">
                                                <input type="range"
                                                       min="0" max="100"
                                                       step="25"
                                                       class="slider"
                                                       id="slide_progress"
                                                       oninput="setInputByRange(this, 'progress')"
                                                       value="{{old('progress',$rec->progress)}}"
                                                >
                                            </div>
                                        </div>

                                        @if(1==0)
                                            <div class="form-group offset-md-4 col-md-4">
                                                <label for="active" style="color: rgb(73, 80, 87);" class="required">Статус
                                                    отчета:
                                                </label>
                                                <?php
                                                $activetypes = [0 => 'черновик', 1 => 'опубликовано'];
                                                ?>
                                                @if ($usrrights['save'] )
                                                    {!! Form::select('active', $activetypes
                                                        , old('active', $rec->active),
                                                         [
                                                         'id' => 'active',
                                                         'class' => 'form-control',
                                                         'placeholder' => '-',
                                                         'required' => 'required',
                                                         ]) !!}
                                                @else
                                                    <div
                                                            class="font-weight-bold">{{$activetypes[$rec->active]??'?'}}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <hr>
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения"
                                            name="update">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $route_index }}"
                                   title="Вернуться в список проектов">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($objcode.'.delete', $rec->id)}}"
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
                @if($rec->id<>-1)
                    <div class="col-md-6">
                        {{--                        @include('objfiles.obj_files')--}}
                        @include('obj_readers._readers')
                    </div>
                @endif
            </div>
        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/ocl_items_edit.js') }}" defer></script>
        <script src="{{ asset('js/setRangeInput.js') }}" defer></script>

    @endif
@endsection
