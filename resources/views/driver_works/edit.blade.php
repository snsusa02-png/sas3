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
        $sysobjid = 1108;
        $thisSysObjId = $sysobjid;
        $sysobjcode = 'driver_works';
        $ThisTitle = "Учет работы водителей";

        $route_index = route('driver_works.index') . '#item_' . $rec->id;


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
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="name">Дата:</label>
                                        @if ($usrrights['edit'])
                                            <input type="date" class="form-control text-center font-weight-bold"
                                                   name="wrkdate" id="wrkdate"
                                                   min="{{$rec->wrkdate_min}}"
                                                   max="{{today()->format('Y-m-d')}}"
                                                   value="{{old('wrkdate',$rec->wrkdate)}}"/>
                                        @else
                                            <div
                                                class="font-weight-bold">{{date_create($rec->wrkdate)->format('d.m.Y')}}
                                                {{ Form::hidden('wrkdate', $rec->wrkdate) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-8">
                                        <label for="name" class="required">Водитель:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="staff_name"
                                                       class="stfname form-control font-weight-bold"
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
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-4 col-md-8">
                                        <label for="name" class="required">Спецтехника/Автомобиль:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="machine_name"
                                                       class="machine_name form-control font-weight-bold"
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
                                    <div class="form-group offset-md-0 col-md-3">
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
                                    <div class="form-group col-md-3">
                                        <label>Продолжительность</label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="stfwrkhrs" id="stfwrkhrs"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->wrkhrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->wrkhrs}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-6 col-md-3">
                                        <label class="">Кол-во рейсов</label>
                                        @if ($usrrights['edit'])
                                            <input type="number" name="raid_qty" id="raid_qty"
                                                   class="form-control text-right" readonly
                                                   min="0" max="24"
                                                   value="{{old('raid_qty',$rec->raid_qty)}}">
                                        @else
                                            <div class="font-weight-bold text-right">{{$rec->raid_qty}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Сумма, &#8381;</label>
                                        @if ($usrrights['edit'])
                                            <input type="number" name="raid_sum" id="raid_sum"
                                                   class="form-control text-right" readonly
                                                   value="{{$rec->raid_sum}}">
                                        @else
                                            <div class="font-weight-bold text-right">{{$rec->raid_sum}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-3 col-md-3">
                                        <label class="">Время простоя, ч</label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="pdt_hrs" id="pdt_hrs"
                                                   class="form-control text-right"
                                                   min="0" max="24" step="0.1" readonly
                                                   value="{{old('pdt_hrs',$rec->pdt_hrs)}}">
                                        @else
                                            <div class="font-weight-bold text-right">{{$rec->pdt_hrs}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Ставка за час, &#8381;</label>
                                        @if ($usrrights['edit'])
                                            <input type="number" name="pdt_cost" id="pdt_cost"
                                                   class="form-control text-right"
                                                   min="0"
                                                   value="{{old('pdt_cost',$rec->pdt_cost)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->pdt_cost}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Сумма, &#8381;</label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="pdt_sum" id="pdt_sum"
                                                   class="form-control text-right"
                                                   readonly value="{{$rec->pdt_sum}}">
                                        @else
                                            <div class="font-weight-bold text-right">{{$rec->pdt_sum}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-3 col-md-3">
                                        <label class="">Время ремонта, ч</label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="repair_hrs" id="repair_hrs"
                                                   class="form-control text-right"
                                                   min="0" max="24" step="0.1" readonly
                                                   value="{{old('repair_hrs',$rec->repair_hrs)}}">
                                        @else
                                            <div class="font-weight-bold text-right">{{$rec->repair_hrs}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Ставка за час, &#8381;</label>
                                        @if ($usrrights['edit'])
                                            <input type="number" name="repair_cost" id="repair_cost"
                                                   class="form-control text-right"
                                                   min="0"
                                                   value="{{old('pdt_cost',$rec->repair_cost)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->repair_cost}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Сумма, &#8381;</label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="repair_sum" id="repair_sum"
                                                   class="form-control text-right"
                                                   readonly value="{{$rec->repair_sum}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->repair_sum}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-9 col-md-3">
                                        <label>Всего, &#8381;</label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="salary_sum" id="salary_sum"
                                                   class="form-control text-right font-weight-bold"
                                                   readonly value="{{$rec->salary_sum}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->salary_sum}}</div>
                                        @endif
                                    </div>
                                </div>


                                <div class="p-2" style="background-color: #fdf2d4;">
                                    <label for="category"><b>Показания спидометра</b>:</label>
                                    <div class="row">
                                        <div class="offset-md-4 col-md-3">
                                            <div class="form-group">
                                                <label for="category" class="">на начало, км:</label>
                                                @if ($usrrights['edit'] )
                                                    <input type="number"
                                                           class="form-control rounded-0 text-right font-weight-bold"
                                                           name="meter_begqty"
                                                           id="meter_begqty"
                                                           min=0

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
                                                <label class="">по окончанию, км:</label>
                                                @if ($usrrights['edit'] )
                                                    <input type="number"
                                                           class="form-control rounded-0 text-right font-weight-bold"
                                                           name="meter_endqty" id="meter_endqty"
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
                                                <label for="fuel_begqty" class="">на начало, л:</label>
                                                @if ($usrrights['edit'] )
                                                    <input type="number"
                                                           class="form-control rounded-0 text-right font-weight-bold"
                                                           name="fuel_begqty"
                                                           id="fuel_begqty"
                                                           min=0

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
                                                <label>получено, л:</label>
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
                                                <label class="">по окончанию, л:</label>
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


                                {{--                                <div class="p-2" style="background-color: #d6f6f6;">--}}
                                {{--                                    <div class="row">--}}
                                {{--                                        <div class="form-group offset-md-0 col-md-12">--}}
                                {{--                                            <label for="wrk_descript">Описание работ:</label>--}}
                                {{--                                            @if ($usrrights['edit'])--}}
                                {{--                                                <textarea name="wrk_descript" class="form-control"--}}
                                {{--                                                          maxlength="360">{{old('wrk_descript',$rec->wrk_descript)}}</textarea>--}}
                                {{--                                            @else--}}
                                {{--                                                <div class="font-weight-bold">{{$rec->wrk_descript}}</div>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}

                                {{--                                </div>--}}


                                <div class="row">
                                    @if(1==0)
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
                                    @endif

                                    <div class="offset-md-4 col-md-8">
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
                                    <a class="btn btn-danger btn-sm"
                                       style="margin-left:24px"
                                       href="{{ route($sysobjcode.'.delete', $rec->id)}}"

                                       onclick="return confirm('Вы действительно хотите удалить запись?')"
                                       title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </a>
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
                                        <i class="fa fa-clone" aria-hidden="true"></i>
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
                    <div class="col-md-4">
                        @include('driver_works._raids')
                        @include('driver_works._breaks')
                        {{--                        @include('objfiles.obj_files')--}}
                        {{--                        @include('obj_readers._readers')--}}
                    </div>
                @endif
            </div>
        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/driver_works_edit.js') }}" defer></script>

    @endif
@endsection
