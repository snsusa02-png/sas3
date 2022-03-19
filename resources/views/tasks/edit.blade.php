@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('events.index');
        header("Location:" . route('events.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>
        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

        <script src="{{ asset('js/callListOrgs.js') }}" defer></script>

        <?php
        $sysobjid = 961;
        $thisSysObjId = 961;
        $sysobjcode = 'tasks';
        $ThisTitle = $rec->name ?? "Задача";


        $route_index = route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;
        $retURL = Request::get('returl') ?? route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;;
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
                <div class="col-md-7">
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
                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($sysobjcode.'.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('id', $rec->id,['id'=>'id']) }}
                                {!! Form::hidden('retURL', $retURL) !!}
                                {{ Form::hidden('ttt', 1) }}
                                {{ Form::hidden('srcsysobjid', $rec->srcsysobjid) }}
                                {{ Form::hidden('srcobjid', $rec->srcobjid) }}

                                @if(isset($rec->src_info))
                                    <div class="row">
                                        <div class="form-group col-md-12 col-sm-6 ">
                                            <label for="name">Основание:</label>
                                            @if(isset($rec->src_url))
                                                <a href="{{$rec->src_url}}" target="_blank">{{$rec->src_info}}</a>
                                            @else
                                                {{$rec->src_info}}
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="form-group col-md-8 col-sm-12 ">
                                        <label for="inituser" class="required">Инициатор:</label>
                                        @if($usrrights['save']??false)
                                            <div class="input-group mb-3 ">
                                                <input type="text"
                                                       class="ac_username form-control"
                                                       value="{{$rec->inituser->name}}">
                                                <input type="text"
                                                       class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; " readonly>
                                                <input type="hidden" name="inituserid" class="ac_userid"
                                                       value="{{$rec->inituserid}}">

                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->inituser->name}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="descript" class="required">Задача (подробно):</label>
                                        @if($usrrights['save']??false)
                                            <textarea class="form-control rounded-0" name="descript" id="descript"
                                                      required
                                                      rows="3">{{ old('descript',$rec->descript) }}</textarea>
                                        @else
                                            <div class="font-weight-bold">{{$rec->descript}}</div>
                                        @endif
                                    </div>
                                </div>

                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group col-md-12 col-sm-6 ">
                                            <label for="name">Задача (кратко):</label>
                                            @if($usrrights['save']??false)
                                                <input type="text" class="form-control text-left"
                                                       name="name" id="name"
                                                       value="{{old('name',$rec->name)}}"
                                                       maxlength="160"/>
                                            @else
                                                <div class="font-weight-bold">{{$rec->name}}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group col-md-12 col-sm-6 ">
                                            <label for="place">Место:</label>
                                            <input type="text" class="form-control text-left" name="event_place"
                                                   value="{{old('place',$rec->place)}}"
                                                   maxlength="160"/>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="form-group col-md-6 col-sm-6">
                                        <label for="plnbegdt">Начать:</label>
                                        @if($usrrights['save']??false)
                                            <input type="datetime-local" class="form-control" name="plnbegdt"
                                                   id="plnbegdt"
                                                   value="{{old('plnbegdt',$rec->plnbegdt)}}"/>
                                        @else
                                            <div
                                                class="font-weight-bold">{{date_create($rec->plnbegdt)->format('d.m.Y H:i')}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6 col-sm-6">
                                        <label for="plnenddt">Закончить до:</label>
                                        @if($usrrights['save']??false)
                                            <input type="datetime-local" class="form-control" name="plnenddt"
                                                   value="{{old('plnenddt',$rec->plnenddt)}}"/>
                                        @else
                                            <div
                                                class="font-weight-bold">{{date_create($rec->plnenddt)->format('d.m.Y H:i')}}</div>
                                        @endif
                                    </div>
                                    @if(1==0)
                                        <div class="form-group col-md-2">
                                            <label for="active" style="color: rgb(73, 80, 87);">Активный:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1,
                                             [
                                             'class' => 'form-control',
                                             ]) !!}
                                        </div>
                                    @endif
                                </div>

                                @if($rec->id==-1)
                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-8 col-sm-12 ">
                                            <label for="name">Исполнитель:</label>
                                            @if($usrrights['save']??false)
                                                <div class="input-group mb-3 ">
                                                    <input type="text"
                                                           class="ac_username form-control font-weight-bold"
                                                           value="{{$rec->exeuser_name}}">
                                                    <input type="text"
                                                           class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3; " readonly>
                                                    <input type="hidden" name="exeuserid" class="ac_userid"
                                                           value="{{$rec->exeuserid}}">
                                                </div>
                                            @else
                                                {{--                                            <div class="font-weight-bold">{{$rec->inituser->name}}</div>--}}
                                            @endif
                                        </div>
                                    </div>
                                @endif


                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group col-md-8">
                                            <label for="address">Категория:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('categoryid', $rec->categories, $rec->categoryid,
                                                 [
                                                 'id' => 'categoryid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$rec->categoryname}}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if($rec->id<>-1)
                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="active" style="color: rgb(73, 80, 87);">Выполнение:</label>
                                            @if($usrrights['save'])
                                                {!! Form::select('statusid', $rec->statuses??[], $rec->statusid,
                                                 [
                                                 'id' => 'statusid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-не указано-',
                                                 ]) !!}
                                            @else
                                                <div
                                                    class="font-weight-bold">{{$rec->exe_statuses()[$rec->statusid]??'?'}}</div>
                                            @endif
                                        </div>
                                        @if(1==0)
                                            <div class="form-group col-md-4" id="progress_div"
                                                 style="display: {{($rec->statusid>1)?'block':'none'}}">
                                                <label>Исполнение, %:</label>
                                                <input type="text"
                                                       class="form-control text-center"
                                                       min="0" max="100"
                                                       placeholder="%"
                                                       id="progress"
                                                       name="progress"
                                                       value="{{old('progress',$rec->progress)}}"
                                                       onchange="setRangeByInput(this, 'slide_progress')"
                                                />
                                                <div class="slidecontainer">
                                                    <input type="range"
                                                           min="0" max="100"
                                                           step="1"
                                                           class="slider"
                                                           id="slide_progress"
                                                           oninput="setInputByRange(this, 'progress')"
                                                           value="{{old('progress',$rec->progress)}}"
                                                    >
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="active" style="color: rgb(73, 80, 87);">Приватность:</label>
                                            @if($usrrights['save']??false)
                                                {!! Form::select('public_lvl', $rec->public_lvls, $rec->public_lvl,
                                                 [
                                                 'id' => 'public_lvl',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                            @else
                                                <div
                                                    class="font-weight-bold">{{$rec->public_lvls[$rec->public_lvl]??'-'}}</div>
                                            @endif
                                        </div>


                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="active" style="color: rgb(73, 80, 87);">Отчет:</label>
                                            @if($usrrights['save']??false)
                                                {!! Form::select('reptypeid', $rec->reptypes, $rec->reptypeid,
                                                 [
                                                 'id' => 'reptypeid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                            @else
                                                <div
                                                    class="font-weight-bold">{{$rec->reptypes[$rec->reptypeid]??'-'}}</div>
                                            @endif
                                        </div>
                                        @if(1==0)
                                            <div class="form-group offset-md-0 col-md-4">
                                                <div id="notify_div"
                                                     style="display: {{(isset($rec->plnbegdt)?'block':'none')}}">
                                                    <label for="active"
                                                           style="color: rgb(73, 80, 87);">Напоминание:</label>
                                                    {!! Form::select('notifytypeid', [0=>'не напоминать',1=>'за 0 минут',2=>'за 5 минут',3=>'за 15 минут'
                                                    ,4=>'за 30 минут'
                                                    ,5=>'за 1 час'
                                                    ,6=>'за 2 часа'
                                                    ,7=>'за 12 часов'
                                                    ,8=>'за 1 день'
                                                    ,9=>'за 2 дня'
                                                    ,10=>'за неделю'
                                                    ], $rec->notifytypeid,
                                                     [
                                                     'id' => 'notifytypeid',
                                                     'class' => 'form-control',
                                                     'placeholder' => '-выбор-',
                                                     ]) !!}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if(1==0)
                                    <div class="form-group">
                                        <label for="descript">Примечание:</label>
                                        <textarea class="form-control rounded-0" name="notes" id="notes"
                                                  rows="2">{{ old('notes',$rec->notes) }}</textarea>
                                    </div>

                                    <div style="background-color: #ddd7ef" class="p-1">
                                        <div class="form-group">
                                            <label for="descript">Тэги:</label>
                                            <textarea class="form-control rounded-0" name="tags" id="tags"
                                                      rows="2">{{ old('tags',$rec->tags) }}</textarea>
                                        </div>


                                    </div>

                                @endif

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
                                            formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif

                                @if (1==0 and $usrrights['take']??false)
                                    <button type="submit"
                                            class="btn btn-warning btn-sm"
                                            style="float:right;"
                                            formaction="{{ route($sysobjcode.'.take', $rec->id)}}"
                                            formmethod="post"
                                            title="Взять в работу"
                                            onclick="return confirm('Вы хотите взять задачу в работу?')"
                                    >Взять в работу
                                    </button>
                                @endif
                                @if (1==1 and $usrrights['breakwork']??false)
                                    <button type="submit"
                                            class="btn btn-outline-secondary btn-sm ml-2"
                                            style=""
                                            formaction="{{ route($sysobjcode.'.break', $rec->id)}}"
                                            formmethod="post"
                                            title="Приостановить работу над задачей"
                                            onclick="return confirm('Вы хотите приостановить обработку заявки?')"
                                    >Приостановить работу
                                    </button>
                                @endif
                                @if (1==1 and $usrrights['adminbreakwork']??false)
                                    <button type="submit"
                                            class="btn btn-warning btn-sm ml-2"
                                            style=""
                                            formaction="{{ route($sysobjcode.'.break', $rec->id)}}"
                                            formmethod="post"
                                            title="Приостановить работу сотрудника с заявкой"
                                            onclick="return confirm('Вы хотите прервать работу текущего исполнителя?')"
                                    >Прервать
                                    </button>
                                @endif
                                @if (1==0 and $usrrights['complete']??false)
                                    <button type="submit"
                                            class="btn btn-primary btn-sm"
                                            style=""
                                            formaction="{{ route($sysobjcode.'.complete', $rec->id)}}"
                                            formmethod="post"
                                            title="Подтвердить, что задача полностью выполнена"
                                            onclick="return confirm('Подтверждаете, что задача полностью выполнена?')"
                                    >
                                        Задача выполнена
                                    </button>
                                @endif
                                @if (1==0 and $usrrights['back2work']??false)
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="float:right;"
                                            formaction="{{ route('equiprqsts.back2work', $rec->id)}}"
                                            formmethod="post"
                                            title="Подтвердить, что нужно вернуть заказ в работу"
                                            onclick="return confirm('Вы действительно желаете вернуть задачу в работу?')"
                                    >
                                        Вернуть в работу
                                    </button>
                                @endif


                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>

                @if (1==1 and $rec->id != -1)
                    <div class="col-md-5">

                        @include('tasks/_users')
                        {{--                        @include('tasks/_reports')--}}

                        @includeif('tasks/_linked_docs')

                        @include('objfiles/obj_files')
                        @include('obj_readers/_readers')
                        @include('tasks.linked_tasks')

                    </div>
                @endif

            </div>
        </div>
        <script src="{{ asset('js/task_edit.js') }}" defer></script>
        <script src="{{ asset('js/setRangeInput.js') }}" defer></script>
    @endif
@endsection
