@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('cursias.index');
        header("Location:" . route('cursias.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 1108;
        $thisSysObjId = $sysobjid;
        $sysobjcode = 'cursias';
        $ThisTitle = "Учет работы спец-техники и автомобилей";

        //$route_index = route('qchecks.edit', $rec->jts_id);
        //$route_index = route('cursias.index') . '#' . $rec->id;
        $route_index = route('cursias.index') . '#item_' . $rec->id;


        $inputReadOnly = '';
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
                <div class="col-md-8">
                    <div class="card p-2 my-2 my-md-3" style="background-color: #f8f8f8">
                        <div class="card-header">
                            <?php
                            $chkdate = date_format(date_create($rec->wrkdate), 'd.m.Y');
                            ?>
                            {{$ThisTitle}} "{{$chkdate}}"
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
                                {{ Form::hidden('ttt', 0) }}


                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-8">
                                        <label for="name" class="required">Работник:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="staff_name"
                                                       class="stfname form-control"
                                                       value="{{old('staff_name',$rec->orgstaff->name)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="staffid" class="staffid" id="staffid"
                                                       value="{{old('staffid',$rec->staffid)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->orgstaff->name}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="name">Дата:</label>
                                        @if ($usrrights['edit'])
                                            <input type="date" class="form-control text-center"
                                                   name="wrkdate"
                                                   max="{{today()->format('Y-m-d')}}"
                                                   value="{{old('wrkdate',$rec->wrkdate)}}"/>
                                        @else
                                            <div
                                                    class="font-weight-bold">{{date_create($rec->wrkdate)->format('d.m.Y')}}
                                                {{ Form::hidden('wrkdate', $rec->wrkdate) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-1 col-md-3">
                                        <label class="required">Начало работы</label>
                                        @if ($usrrights['edit'])
                                            <input type="time" name="begtime" id="begtime"
                                                   class="form-control text-center"
{{--                                                   max="{{$rec->maxtime}}"--}}
                                                   value="{{old('begtime',$rec->begtime)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->begtime}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Окончание</label>
                                        @if ($usrrights['edit'])
                                            <input type="time" name="endtime" id="endtime"
                                                   class="form-control text-center"
{{--                                                   max="{{$rec->maxtime}}"--}}
                                                   value="{{old('endtime',$rec->endtime)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->endtime}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>Перерыв, ч</label>
                                        @if ($usrrights['edit'])
                                            <input type="number" name="break_hrs" id="break_hrs"
                                                   class="form-control text-center"
                                                   min=0 max="1"
                                                   value="{{old('break_hrs',$rec->break_hrs)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->break_hrs}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Продолжительность</label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="stfwrkhrs" id="stfwrkhrs"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->stfwrkhrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->stfwrkhrs}}</div>
                                        @endif
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="name" class="required">Спецтехника/Автомобиль:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="machine_name"
                                                       class="machine_name form-control"
                                                       value="{{old('machine_name',$rec->machine->info)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="machineid" class="machineid" id="machineid"
                                                       value="{{old('machineid',$rec->machineid)}}">
                                                {{--                                                <input type="hidden" name="orgid" class="orgid" id="orgid"--}}
                                                {{--                                                       value="{{old('orgid',$rec->orgid)}}">--}}
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->machine->name}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-8">
                                        <label for="mot_id" class="required">Режим работы техники:</label>
                                        @if ($usrrights['edit'] )
                                            {!! Form::select('mot_id', $rec->mots??[], old('mot_id',$rec->mot_id),
                                             [
                                                 'id' => 'mot_id',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->mchn_opertype->name??'-'}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label title="для спецтехники">Время работы ДВС, ч</label>
                                        @if ($usrrights['edit'])
                                            <input type="number" name="mchnwrkhrs" id="mchnwrkhrs"
                                                   min="0" max="24"
                                                   class="form-control text-center"
                                                   value="{{$rec->mchnwrkhrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->mchnwrkhrs}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-2" style="background-color: #fdf2d4;">
                                    <label for="category"><b>Показания спидометра</b>:</label>
                                    <div class="row">
                                        <div class="offset-md-4 col-md-3">
                                            <div class="form-group">
                                                <label for="category" class="required">на начало, км:</label>
                                                @if ($usrrights['edit'] )
                                                    <input type="number"
                                                           class="form-control rounded-0 text-right font-weight-bold"
                                                           name="meter_begqty"
                                                           id="meter_begqty"
                                                           min=0
                                                           required
                                                           value="{{old('meter_begqty',$rec->meter_begqty)}}">
                                                @else
                                                    <div class="font-weight-bold">
                                                        <div
                                                                class="font-weight-bold text-right">{{$rec->meter_begqty??'-'}}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="offset-md-0 col-md-3">
                                            <div class="form-group">
                                                <label for="category">по окончанию, км:</label>
                                                @if ($usrrights['edit'] )
                                                    <input type="number"
                                                           class="form-control rounded-0 text-right font-weight-bold"
                                                           name="meter_endqty"
                                                           id="meter_endqty"
                                                           min=0
                                                           value="{{old('meter_endqty',$rec->meter_endqty)}}">
                                                @else
                                                    <div class="font-weight-bold text-right">
                                                        <div class="font-weight-bold">{{$rec->meter_endqty??'-'}}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="offset-md-0 col-md-2">
                                            <div class="form-group">
                                                <label for="category">Пробег, км:</label>
                                                <input type="number"
                                                       class="form-control rounded-0 text-right font-weight-bold"
                                                       id="meter_qty"
                                                       readonly
                                                       value="{{old('meter_qty',$rec->meter_qty)}}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-2" style="background-color: #d8f6c3;">
                                    <label for="category"><b>Топливо</b>:</label>
                                    <div class="row">
                                        <div class="offset-md-1 col-md-3">
                                            <div class="form-group">
                                                <label for="category" class="required">на начало, л:</label>
                                                @if ($usrrights['edit'] )
                                                    <input type="number"
                                                           class="form-control rounded-0 text-right font-weight-bold"
                                                           name="fuel_begqty"
                                                           id="fuel_begqty"
                                                           min=0
                                                           required
                                                           value="{{old('fuel_begqty',$rec->fuel_begqty)}}">
                                                @else
                                                    <div class="font-weight-bold">
                                                        <div
                                                                class="font-weight-bold text-right">{{$rec->fuel_begqty??'-'}}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="offset-md-0 col-md-3">
                                            <div class="form-group">
                                                <label for="category">получено, л:</label>
                                                @if ($usrrights['edit'] )
                                                    <input type="number"
                                                           class="form-control rounded-0 text-right font-weight-bold"
                                                           name="fuel_inpqty"
                                                           id="fuel_inpqty"
                                                           min=0
                                                           value="{{old('fuel_inpqty',$rec->fuel_inpqty)}}">
                                                @else
                                                    <div class="font-weight-bold text-right">
                                                        <div class="font-weight-bold">{{$rec->fuel_inpqty??'0'}}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="offset-md-0 col-md-3">
                                            <div class="form-group">
                                                <label for="category">по окончанию, л:</label>
                                                @if ($usrrights['edit'] )
                                                    <input type="number"
                                                           class="form-control rounded-0 text-right font-weight-bold"
                                                           name="fuel_endqty"
                                                           id="fuel_endqty"
                                                           min=0
                                                           value="{{old('fuel_endqty',$rec->fuel_endqty)}}">
                                                @else
                                                    <div class="font-weight-bold text-right">
                                                        <div class="font-weight-bold">{{$rec->fuel_endqty??'-'}}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="offset-md-0 col-md-2">
                                            <div class="form-group">
                                                <label for="category">Расход, л:</label>
                                                <div class="font-weight-bold text-right">
                                                    <div
                                                            class="font-weight-bold"><input type="text"
                                                                                            class="form-control rounded-0 text-right font-weight-bold"
                                                                                            id="fuel_spentqty"
                                                                                            readonly
                                                                                            value="{{old('fuel_spentqty',$rec->fuel_spentqty)}}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="p-2" style="background-color: #d6f6f6;">
                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="wrk_descript">Описание работ:</label>
                                            @if ($usrrights['edit'])
                                                <textarea name="wrk_descript" class="form-control"
                                                          maxlength="360">{{old('wrk_descript',$rec->wrk_descript)}}</textarea>
                                            @else
                                                <div class="font-weight-bold">{{$rec->wrk_descript}}</div>
                                            @endif
                                        </div>
                                    </div>

                                    @if(1==1)
                                        <div class="row">
                                            <div class="form-group offset-md-0 col-md-6">
                                                <label for="buildobjid">для Объекта:</label>
                                                @if ($usrrights['edit'])
                                                    {!! Form::select('buildobjid', $rec->buildobjs??[], $rec->buildobjid,
                                                     [
                                                     'id' => 'buildobjid',
                                                     'class' => 'form-control',
                                                     'placeholder' => '-выбор-',
                                                     ]) !!}
                                                @else
                                                    <div class="font-weight-bold">{{$rec->buildobj->name}}</div>
                                                @endif
                                            </div>

                                            <div class="form-group col-md-6">
                                                <label for="buildopertypeid" class="required">для Вида работ:</label>
                                                @if ($usrrights['edit'] )
                                                    {!! Form::select('buildopertypeid'
        , $rec->buildopertypes??[]
        , old('buildopertypeid',$rec->buildopertypeid),
                                                     [
                                                         'id' => 'buildopertypeid',
                                                     'class' => 'form-control',
                                                     'placeholder' => '-выбор-',
                                                     ]) !!}
                                                @else
                                                    <div
                                                            class="font-weight-bold">{{$rec->buildopertype->name??'-'}}</div>
                                                @endif
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="form-group offset-md-3 col-md-9">
                                                <label for="orgcontractid">Подрядчик: Договор:</label>
                                                @if ($usrrights['edit'] and isset($rec->orgcontracts))
                                                    {!! Form::select('orgcontractid', $rec->orgcontracts??[], $rec->orgcontractid,
                                                     [
                                                     'id' => 'orgcontractid',
                                                     'class' => 'form-control',
                                                     'placeholder' => '',
                                                     ]) !!}
                                                @else
                                                    <div class="">
                                                        <b>{{$rec->contract->org->name}}</b>
                                                        / {{$rec->contract->shortInfo}}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>


                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="address">Статус:</label>
                                        @if ($usrrights['change_status'])
                                            {!! Form::select('statusid', $rec->statuses??[], $rec->statusid,
                                             [
                                             'class' => 'form-control',
                                             ]) !!}
                                        @else
                                            <div
                                                    class="font-weight-bold">{{$rec->statuses[$rec->statusid]??'-?-'}}</div>
                                        @endif
                                    </div>

                                    <div class="offset-md-0 col-md-8">
                                        <div class="form-group">
                                            <label for="decision">Примечание:</label>
                                            @if ($usrrights['edit'] or $usrrights['change_status'])
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


                                <hr>
                                @if ($usrrights['save'] or $usrrights['change_status'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения"
                                            name="update">
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
                                            formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                @if ($rec->id != -1 and $usrrights['make_template']??true)
                                    <button type="submit"
                                            class="btn btn-info btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($sysobjcode . '.make_template', $rec->id)}}"
                                            formmethod="get"
                                            onclick="return confirm('Создать шаблон для новых записей на основе данных текущей записи?')"
                                            title="Создать шаблон на основе данных записи"
                                    >
                                        <i class="fa fa-arrow-circle-o-down" aria-hidden="true"></i>
                                    </button>

                                    @if(isset($rec->template_id))
                                        <a href="{{ route('user_templates.delete', $rec->template_id)}}?returl={{Request::url()}}"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Удалить текущий шаблон?')"
                                           title="Удалить текущий шаблон"
                                        >
                                            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                @endif
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>
                @if($rec->id<>-1)
                    <div class="col-md-6">
                        {{--                        @include('objfiles.obj_files')--}}
                        {{--                        @include('obj_readers._readers')--}}
                    </div>
                @endif
            </div>
        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/cursias_edit.js') }}" defer></script>

    @endif
@endsection
