@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('driver_works.index');
        header("Location:" . route('driver_works.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 1109;
        $thisSysObjId = $sysobjid;
        $sysobjcode = 'dw_breaks';
        $objcode = $sysobjcode;
        $ThisTitle = "Регистрация простоя техники";

        $route_index = route('driver_works.edit', $rec->dw_id) . '#item_' . $rec->id;

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
        </style>
        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-6">
                    <div class="card p-2 my-2 my-md-3" style="background-color: #f8f8f8">
                        <div class="card-header">
                            <?php
                            $chkdate = date_format(date_create($rec->driver_work->wrkdate), 'd.m.Y');
                            ?>
                            {{$ThisTitle}} "<b>{{$rec->driver_work->orgstaff->name}}</b>, {{$chkdate}}"
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
                                  action="{{ route($sysobjcode.'.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('dw_id', $rec->dw_id) }}
                                {{ Form::hidden('wrkdate', $rec->driver_work->wrkdate, ['id'=>'wrkdate']) }}
                                {{ Form::hidden('ttt', 0) }}


                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-6">
                                        <label for="mot_id" class="required">Тип простоя:</label>
                                        @if ($usrrights['save'] )
                                            {!! Form::select('breaktypeid', $rec->breaktypes??[], old('breaktypeid',$rec->breaktypeid),
                                             [
                                                 'id' => 'breaktypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        @else
                                            <div
                                                class="font-weight-bold">{{$rec->breaktypes[$rec->breaktypeid]??'-'}}</div>
                                        @endif
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="form-group offset-md-3 col-md-3">
                                        <label class="required">Начало</label>
                                        @if ($usrrights['save'])
                                            <input type="time" name="begtime" id="begtime" class="form-control text-center"
                                                   required
                                                   value="{{old('begtime',$rec->begtime)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->begtime}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="required">Окончание</label>
                                        @if ($usrrights['save'])
                                            <input type="time" name="endtime" id="endtime" class="form-control text-center"
                                                   required
                                                   value="{{old('endtime',$rec->endtime)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->endtime}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group col-md-3">
                                        <label>Всего, ч</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="breakhrs" id="breakhrs"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->breakhrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->breakhrs}}</div>
                                        @endif
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="offset-md-0 col-md-12">
                                        <div class="form-group">
                                            <label class="required">Объяснение:</label>
                                            @if ($usrrights['save'] )
                                                <input type="text" class="form-control rounded-0 font-weight-bold"
                                                       name="reason" maxlength="160"
                                                       id="reason" required
                                                       value="{{old('reason',$rec->reason)}}">
                                            @else
                                                <div class="font-weight-bold">
                                                    <div class="font-weight-bold">{{$rec->reason??'-'}}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>


                                @if(1==0)
                                    <div class="row">
                                        <div class="offset-md-0 col-md-12">
                                            <div class="form-group">
                                                <label for="decision">Примечание:</label>
                                                @if ($usrrights['save'] )
                                                    <textarea class="form-control rounded-0"
                                                              name="notes" id="notes"
                                                              rows="1">{{old('notes',$rec->notes)}}</textarea>
                                                @else
                                                    <div class="font-weight-bold">
                                                        <div class="font-weight-bold">{{$rec->notes??'-'}}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
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
                                    <button
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($objcode.'.delete', $rec->id)}}"
                                            formmethod="get"
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
                    <div class="col-md-4">
                        @include('objfiles.obj_files')
                        {{--                        @include('obj_readers._readers')--}}
                    </div>
                @endif
            </div>
        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/dw_breaks_edit.js') }}" defer></script>

    @endif
@endsection
