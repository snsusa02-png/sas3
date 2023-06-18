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
        $sysobjid = 1106;
        $thisSysObjId = $sysobjid;
        $thisSysObjCode = 'mchn_raids';
        $sysobjcode = $thisSysObjCode;
        $ThisTitle = "Регистрация рейса";

        //$route_index = route($thisSysObjCode . '.index') . '#item_' . $rec->id;
        $retURL = \Request::get('returl') ?? $rec->retURL ?? (route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id);

        $inputReadOnly = '';

        $in_gk_hide = ($rec->in_gk == 0) ? 'display:none;' : '';
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
                <div class="col-md-10">
                    <div class="card p-2 my-2 my-md-3" style="background-color: #f8f8f8">
                        <div class="card-header">
                            <i class="fa fa-truck text-info" aria-hidden="true"></i>
                            {{$ThisTitle}}
                            <a class="btn btn-close btn-light btn-sm"
                               style="float:right;"
                               href="{{ $retURL }}"
                               title="Вернуться в список">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="card-body">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($thisSysObjCode.'.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('ttt', 0) }}
                                {{ Form::hidden('dw_id', $rec->dw_id) }}
                                {{ Form::hidden('in_gk', $rec->in_gk,['id'=>'in_gk']) }}
                                {!! Form::hidden('returl', $retURL) !!}

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="name" class="required">Вид работ:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('opertypeid', $rec->opertypes??[], old('opertypeid',$rec->opertypeid),
                                             [
                                                 'id' => 'opertypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->opertype->name}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="name" class="required">Техника:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="machine_name" id="machine_name"
                                                       class="ac_name machine_name form-control font-weight-bold"
                                                       value="{{old('machine_name',$rec->machine->RegNumName)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="machineid" class="ac_id machineid"
                                                       id="machineid"
                                                       value="{{old('machineid',$rec->machineid)}}">
                                                <a class="btn btn-light id_lnk" id="machineid_lnk"
                                                   data-id="machineid" data-obj="machines" target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->machine->RegNumName}}</div>
                                            <input type="hidden" name="machineid" id="machineid"
                                                   value="{{$rec->machineid}}">
                                        @endif

                                    </div>

                                    <div class="form-group col-md-4 driver_info" style="{{$in_gk_hide}}">
                                        <label for="name" class="required">Водитель: </label>
                                        {{--                                        @if ($usrrights['edit_dmd'])--}}
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="drivername" id="drivername"
                                                       class="driver_name form-control ac_name font-weight-bold"
                                                       value="{{old('drivername',$rec->driver->name)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="driverid" class="ac_id" id="driverid"
                                                       value="{{old('driverid', $rec->driverid)}}">
                                                <a class="btn btn-light id_lnk" id="driverid_lnk"
                                                   data-id="driverid" data-obj="orgstaff" target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->driver->name}}</div>
                                            <input type="hidden" name="driverid" id="driverid"
                                                   value="{{$rec->driverid}}">
                                        @endif
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="required">Дата:</label>
                                        @if(isset($rec->dw_id))
                                            <a href="{{route('driver_works.edit',$rec->dw_id)}}" class="float-right"
                                               style="{{$in_gk_hide}}">Отчет:
                                                >>></a>
                                        @endif
                                        {{--                                        @if ($usrrights['edit_dmd'])--}}
                                        @if ($usrrights['edit'])
                                            <input type="date" class="form-control text-center font-weight-bold"
                                                   name="wrkdate" id="wrkdate" required
                                                   min="{{$rec->wrkdate_min}}"
                                                   max="{{today()->format('Y-m-d')}}"
                                                   value="{{old('wrkdate',$rec->wrkdate)}}"/>
                                        @else
                                            <div
                                                class="font-weight-bold text-center">{{date_create($rec->wrkdate)->format('d.m.Y')}}
                                                {{ Form::hidden('wrkdate', $rec->wrkdate,['id'=>'wrkdate']) }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="required">Тип работы:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('wrktypeid', $rec->main_wrktypes??[], old('wrktypeid',$rec->wrktypeid),
                                             [
                                                 'id' => 'wrktypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->wrktype->name}}</div>
                                        @endif
                                    </div>

                                    <div class="offset-md-0 col-md-6">
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

                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-2">
                                            <label class="">время:</label>
                                            @if ($usrrights['edit'])
                                                <input type="time" name="begtime" id="begtime" required0
                                                       class="form-control text-center font-weight-bold"
                                                       {{--                                                   max="{{$rec->maxtime}}"--}}
                                                       value="{{old('begtime',$rec->begtime)}}">
                                            @else
                                                <div class="font-weight-bold text-center">{{$rec->begtime}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="required0">Окончание:</label>
                                            @if ($usrrights['edit'])
                                                <input type="date" class="form-control text-center font-weight-bold"
                                                       name="wrkenddate" id="wrkenddate" required0
                                                       min="{{$rec->wrkdate_min}}"
                                                       max="{{today()->format('Y-m-d')}}"
                                                       value="{{old('wrkenddate',$rec->wrkenddate)}}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{date_create($rec->wrkenddate)->format('d.m.Y')}}
                                                    {{ Form::hidden('wrkenddate', $rec->wrkenddate,['id'=>'wrkenddate']) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label class="">время:</label>
                                            @if ($usrrights['edit'])

                                                <input type="time" name="endtime" id="endtime" required0
                                                       class="form-control text-center font-weight-bold"
                                                       {{--                                                   max="{{$rec->maxtime}}"--}}
                                                       value="{{old('endtime',$rec->endtime)}}">
                                            @else
                                                <div class="font-weight-bold text-center">
                                                    {{$rec->endtime}}</div>
                                            @endif
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label>Всего, ч </label>
                                            @if ($usrrights['edit'])
                                                <input type="text" name="stfwrkhrs" id="stfwrkhrs"
                                                       class="form-control text-center"
                                                       readonly value="{{$rec->stfwrkhrs}}">
                                            @else
                                                <div class="font-weight-bold text-center">{{$rec->stfwrkhrs}}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="row salary_info" style="{{$in_gk_hide}}">

                                    <div class="form-group offset-md-6 col-md-2">
                                        <label for="name" class="" id="lbl_raid_qty">Число рейсов:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="raid_qty" id="raid_qty" readonly
                                                       class="form-control text-right font-weight-bold"
                                                       min="0" step="1" max="99"
                                                       value="{{old('raid_qty',$rec->raid_qty)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->raid_qty}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-2">
                                        <label for="name" class="required" title="ЗП водителя за 1 рейс">ЗП за рейс,
                                            &#8381;:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="raid_salary" id="raid_salary" required
                                                       title="ЗП водителя за 1 рейс"
                                                       class="form-control text-right font-weight-bold"
                                                       min="0" step="0.01"
                                                       value="{{old('raid_salary',$rec->raid_salary)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->raid_salary}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group col-md-2">
                                        <label>Всего, &#8381; </label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="salary" id="salary"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->raid_salary*$rec->raid_qty}}">
                                        @else
                                            <div
                                                class="font-weight-bold text-center">{{$rec->raid_salary*$rec->raid_qty}}</div>
                                        @endif
                                    </div>

                                </div>


                                @if(1==0)
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
                                                            <div
                                                                class="font-weight-bold">{{$rec->meter_endqty??'-'}}</div>
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
                                                            <div
                                                                class="font-weight-bold">{{$rec->fuel_inpqty??'0'}}</div>
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
                                                            <div
                                                                class="font-weight-bold">{{$rec->fuel_endqty??'-'}}</div>
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
                                                    <label for="buildopertypeid" class="required">для Вида
                                                        работ:</label>
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
                                @endif


                                <hr>
                                @if ($usrrights['save'] or $usrrights['change_status'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения"
                                            name="update">
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
                                            formaction="{{ route($thisSysObjCode.'.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                @if ($usrrights['admindelete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px; margin-right:8px;"
                                            formaction="{{ route($thisSysObjCode.'.admindelete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Заявка будет удалена административно - без учета ограничений!\n\nПродолжать?')"
                                            title="Административно удалить заявку"
                                    >
                                        <i class="fa fa-bomb" aria-hidden="true"></i>
                                    </button>
                                @endif

                                @if (1==1 and $rec->id != -1 and $usrrights['create'] and $usrrights['make_template']??true)
                                    <button type="submit"
                                            class="btn btn-info btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($thisSysObjCode . '.make_template', $rec->id)}}"
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
                                @if (1==1 and $rec->id != -1 and $usrrights['create']??true)
                                    <button type="submit"
                                            class="btn btn-warning btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($thisSysObjCode . '.clone', $rec->id)}}"
                                            formmethod="get"
                                            onclick="return confirm('Создать копию записи?')"
                                            title="Создать копию записи"
                                    >
                                        <i class="fa fa-files-o" aria-hidden="true"></i>
                                    </button>
                                @endif
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>

                    @include('mchn_raids._opers')

                </div>
                @if($rec->id<>-1)
                    <div class="col-md-2">
                        @include('objfiles.obj_files')
                        {{--                        @include('obj_readers._readers')--}}
                    </div>
                @endif
            </div>

            @if($rec->id<>-1)
                <div class="row">
                    <div class="col-md-10">
                        @include('obj_finopers._finopers')
                    </div>

                </div>
            @endif
        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/mchn_raid_edit.js') }}" defer></script>

    @endif
@endsection
