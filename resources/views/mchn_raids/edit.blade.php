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

                                    <div class="form-group col-md-4">
                                        <label for="name" class="required">Водитель:
                                        </label>
                                        @if(isset($rec->dw_id))
                                            <a href="{{route('driver_works.edit',$rec->dw_id)}}" class="float-right">Отчет:
                                                >>></a>
                                        @endif
                                        @if ($usrrights['edit_dmd'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="drivername" id="drivername"
                                                       class="driver_name form-control ac_name font-weight-bold"
                                                       value="{{old('drivername',$rec->driver->name)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="driverid" class="ac_id" id="driverid"
                                                       value="{{old('driverid', $rec->driverid)}}">
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
                                        @if ($usrrights['edit_dmd'])
                                            <input type="date" class="form-control text-center font-weight-bold"
                                                   name="wrkdate" id="wrkdate" required
                                                   max="{{today()->format('Y-m-d')}}"
                                                   value="{{old('wrkdate',$rec->wrkdate)}}"/>
                                        @else
                                            <div
                                                class="font-weight-bold text-center">{{date_create($rec->wrkdate)->format('d.m.Y')}}
                                                {{ Form::hidden('wrkdate', $rec->wrkdate,['id'=>'wrkdate']) }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-2">
                                        <label class="required">Начало</label>
                                        @if ($usrrights['edit'])
                                            <input type="time" name="begtime" id="begtime" required
                                                   class="form-control text-center font-weight-bold"
                                                   {{--                                                   max="{{$rec->maxtime}}"--}}
                                                   value="{{old('begtime',$rec->begtime)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->begtime}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>Окончание</label>
                                        @if ($usrrights['edit'])
                                            <input type="time" name="endtime" id="endtime" required
                                                   class="form-control text-center font-weight-bold"
                                                   {{--                                                   max="{{$rec->maxtime}}"--}}
                                                   value="{{old('endtime',$rec->endtime)}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->endtime}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>Всего, ч </label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="mchnwrkhrs" id="mchnwrkhrs"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->mchnwrkhrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->mchnwrkhrs}}</div>
                                        @endif
                                    </div>

                                </div>


                                <div class="row">

                                </div>

                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="name" class="required">Поставщик:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="org_name" required id="suporg_name"
                                                           class="ac_name ac_suporg_name form-control font-weight-bold"
                                                           value="{{old('suporg_name',$rec->suporg->info)}}">
                                                    <input type="text" class="form-control text-center small ac_status"
                                                           title=""
                                                           style="display: none; border: #d7f3e3; max-width: 30px"
                                                           readonly>
                                                    <input type="hidden" name="suporgid" class="ac_id" id="suporgid"
                                                           value="{{old('suporgid',$rec->suporgid)}}">
                                                    <a class="btn btn-light id_lnk" data-id="suporgid" data-obj="orgs"
                                                       target="_blank">
                                                        <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                                <div></div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->suporg->info}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="name" class="required">Место загрузки:</label>
                                            @if ($usrrights['edit'])
                                                {{--                                            <div class="input-group mb-3 ">--}}
                                                {{--                                                <input type="text" name="load_placename" id="load_placename" required--}}
                                                {{--                                                       class="ac_name load_placename form-control font-weight-bold"--}}
                                                {{--                                                       value="{{old('load_placename',$rec->load_placename)}}">--}}
                                                {{--                                                <input type="text" class="form-control text-center small ac_status"--}}
                                                {{--                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>--}}
                                                {{--                                                <input type="hidden" name="load_placeid" class="load_placeid ac_id"--}}
                                                {{--                                                       id="load_placeid"--}}
                                                {{--                                                       value="{{old('load_placeid',$rec->load_placeid)}}">--}}
                                                {{--                                                <a class="btn btn-light id_lnk" data-id="load_placeid" data-obj="places"--}}
                                                {{--                                                   target="_blank">--}}
                                                {{--                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>--}}
                                                {{--                                                </a>--}}
                                                {{--                                            </div>--}}
                                                <div class="input-group">
                                                    {!! Form::select('load_placeid', $rec->load_places??[],
                                                        old('load_placeid',$rec->load_placeid),
                                                        [
                                                        'id' => 'load_placeid',
                                                        'class' => 'form-control small',
                                                        'placeholder' => '',
                                                        'required' => 'required',
                                                        ]) !!}
                                                </div>

                                            @else
                                                <div class="font-weight-bold">{{$rec->load_placename}}</div>
                                            @endif
                                        </div>

                                    </div>

                                    <div class="row">

                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="name" class="required">Покупаемый товар:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group">
                                                    <input type="text" class="form-control font-weight-bold ac_name "
                                                           name="load_cargo_name" id="load_cargo_name" required
                                                           maxlength="60"
                                                           value="{{$rec->load_refitem->name}}"
                                                           autocomplete="off"
                                                    />
                                                    <input type="text" class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3;" readonly>
                                                    <input type="hidden" name="load_refitmid" id="load_refitmid"
                                                           class="ac_id"
                                                           value="{{$rec->load_refitmid}}">
                                                    <a class="btn btn-light id_lnk" id="refitmid_lnk"
                                                       data-id="load_refitmid"
                                                       data-obj="refitems" target="_blank">
                                                        <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->load_refitem->name}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-2">
                                            <label for="name" class="required">Цена покупки:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="number" name="load_price" id="load_price"
                                                           class="form-control text-right font-weight-bold" required
                                                           min="0" step="0.01" readonly
                                                           value="{{old('load_price',$rec->load_price)}}">
                                                </div>
                                            @else
                                                <div
                                                    class="font-weight-bold">{{number_format($rec->load_price,2)}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="name" class="required">Покупает:</label>
                                            @if ($usrrights['edit'])
                                                {!! Form::select('load_ownorgid', $rec->ownorgs??[],
                                                    old('load_ownorgid',$rec->load_ownorgid),
                                                 [
                                                     'id' => 'load_ownorgid',
                                                 'class' => 'form-control small font-weight-bold',
                                                 'placeholder' => '',
                                                 'required' => 'required',
                                                 ]) !!}
                                        </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->load_ownorg->name}}</div>
                                        @endif


                                    </div>

                                    <div class="row">

                                        <div class="form-group offset-md-6 col-md-3">
                                            <label for="name" class="required">Объем загрузки,
                                                <span class="font-weight-bold"
                                                      id="load_qty_unit">{{$rec->load_refitem->unittype->name??'ЕИ'}}</span>:
                                            </label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="number" name="load_qty" id="load_qty"
                                                           class="form-control text-right font-weight-bold" required
                                                           min="0" step="0.01" max="999"
                                                           value="{{old('load_qty',$rec->load_qty)}}">

                                                    {{--                                                {!! Form::select('qty_unittypeid', $rec->unittypes??[],--}}
                                                    {{--                                                    old('qty_unittypeid',$rec->qty_unittypeid),--}}
                                                    {{--                                                 [--}}
                                                    {{--                                                     'id' => 'qty_unittypeid',--}}
                                                    {{--                                                 'class' => 'form-control small',--}}
                                                    {{--                                                 'placeholder' => '',--}}
                                                    {{--                                                 'required' => 'required',--}}
                                                    {{--                                                 'style' => 'max-width:36%',--}}
                                                    {{--                                                 ]) !!}--}}
                                                </div>
                                            @else
                                                <div
                                                    class="font-weight-bold">{{$rec->load_qty}} {{$rec->qty_unit}}</div>
                                            @endif
                                        </div>


                                        <div class="form-group col-md-3">
                                            <label>Стоимость загрузки</label>
                                            @if ($usrrights['edit'])
                                                <input type="text" name="load_sum" id="load_sum" required
                                                       class="form-control text-center font-weight-bold"
                                                       readonly value="{{$rec->load_sum}}">
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{number_format($rec->load_sum,2)}}</div>
                                            @endif
                                        </div>
                                    </div>


                                    <div class="row oth_9">

                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="name" class="required">Продаваемый товар:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group">
                                                    <input type="text" class="form-control font-weight-bold ac_name "
                                                           name="unload_cargo_name" id="unload_cargo_name" required
                                                           maxlength="60"
                                                           value="{{$rec->unload_refitem->name}}"
                                                           autocomplete="off"
                                                    />
                                                    <input type="text" class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3;" readonly>
                                                    <input type="hidden" name="unload_refitmid" id="unload_refitmid"
                                                           class="ac_id"
                                                           value="{{$rec->unload_refitmid}}">
                                                    <a class="btn btn-light id_lnk" id="refitmid2_lnk"
                                                       data-id="unload_refitmid"
                                                       data-obj="refitems" target="_blank">
                                                        <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->unload_refitem->name}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-2">
                                            <label for="name" class="required">Цена продажи:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="number" name="unload_price" id="unload_price"
                                                           class="form-control text-right font-weight-bold" required
                                                           min="0" step="0.01"
                                                           value="{{old('unload_price',$rec->unload_price)}}">
                                                </div>
                                            @else
                                                <div
                                                    class="font-weight-bold">{{number_format($rec->unload_price,2)}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="name" class="required">Продает:</label>
                                            @if ($usrrights['edit'])
                                                {!! Form::select('unload_ownorgid', $rec->ownorgs??[],
                                                    old('unload_ownorgid',$rec->unload_ownorgid),
                                                 [
                                                     'id' => 'unload_ownorgid',
                                                 'class' => 'form-control small font-weight-bold',
                                                 'placeholder' => '',
                                                 'required' => 'required',
                                                 ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$rec->unload_ownorg->name}}</div>
                                            @endif
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-6 col-md-3">
                                            <label for="name" class="required">Подписанный объем, <span
                                                    class="font-weight-bold"
                                                    id="unload_qty_unit">{{$rec->unload_refitem->unittype->name??'ЕИ'}}</span>:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="number" name="unload_qty" id="unload_qty"
                                                           class="form-control text-right font-weight-bold"
                                                           min="0" step="0.01" max="999" required
                                                           value="{{old('unload_qty',$rec->unload_qty)}}">
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->unload_qty}}</div>
                                            @endif
                                        </div>

                                        {{--                                    <div class="form-group offset-md-0 col-md-2">--}}
                                        {{--                                        <label for="name" class="required">Цена отпуска:</label>--}}
                                        {{--                                        @if ($usrrights['edit'])--}}
                                        {{--                                            <div class="input-group mb-3 ">--}}
                                        {{--                                                <input type="number" name="unload_price" id="unload_price"--}}
                                        {{--                                                       class="form-control text-right font-weight-bold"--}}
                                        {{--                                                       min="0" step="0.01" required--}}
                                        {{--                                                       value="{{old('unload_price',$rec->unload_price)}}">--}}
                                        {{--                                            </div>--}}
                                        {{--                                        @else--}}
                                        {{--                                            <div class="font-weight-bold">{{number_format($rec->unload_price,2)}}</div>--}}
                                        {{--                                        @endif--}}
                                        {{--                                    </div>--}}

                                        <div class="form-group offset-md-0 col-md-3">
                                            <label>Стоимость</label>
                                            @if ($usrrights['edit'])
                                                <input type="text" name="unload_sum" id="unload_sum"
                                                       class="form-control text-center font-weight-bold"
                                                       readonly value="{{$rec->unload_sum}}">
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{number_format($rec->unload_sum,2)}}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row ownorg_transfer">
                                        <div class="form-group offset-md-9 col-md-3">
                                            <label>Сумма передачи между ГК</label>
                                            @if ($usrrights['edit'])
                                                <input type="number" name="ownorg_sum" id="ownorg_sum"
                                                       class="form-control text-center font-weight-bold" step="0.01"
                                                       min="{{$rec->load_sum??0}}" max="{{$rec->unload_sum??0}}"
                                                       value="{{$rec->ownorg_sum}}">
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{number_format($rec->ownorg_sum,2)}}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="name" class="required">Заказчик:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="org_name" required id="org_name"
                                                           class="ac_name ac_org_name form-control font-weight-bold"
                                                           value="{{old('org_name',$rec->org_name)}}">
                                                    <input type="text" class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3; max-width: 30px"
                                                           readonly>
                                                    <input type="hidden" name="orgid" class="ac_id" id="orgid"
                                                           value="{{old('orgid',$rec->orgid)}}">
                                                    <a class="btn btn-light id_lnk" data-id="orgid" data-obj="orgs"
                                                       target="_blank">
                                                        <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->org_name}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="name" class="required">Место выгрузки:</label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="unload_placename" id="unload_placename"
                                                           required
                                                           class="ac_name unload_placename form-control font-weight-bold"
                                                           value="{{old('unload_placename',$rec->unload_placename)}}">
                                                    <input type="text" class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3; max-width: 30px"
                                                           readonly>
                                                    <input type="hidden" name="unload_placeid" class="ac_id"
                                                           id="unload_placeid"
                                                           value="{{old('unload_placeid',$rec->unload_placeid)}}">
                                                    <a class="btn btn-light id_lnk" data-id="unload_placeid"
                                                       data-obj="places"
                                                       target="_blank">
                                                        <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                                {{--                                            <div class="input-group">--}}
                                                {{--                                                {!! Form::select('unload_placeid', $rec->unload_places??[],--}}
                                                {{--                                                    old('unload_placeid',$rec->unload_placeid),--}}
                                                {{--                                                    [--}}
                                                {{--                                                    'id' => 'unload_placeid',--}}
                                                {{--                                                    'class' => 'form-control small',--}}
                                                {{--                                                    'placeholder' => '',--}}
                                                {{--                                                    'required' => 'required',--}}
                                                {{--                                                    ]) !!}--}}
                                                {{--                                            </div>--}}

                                            @else
                                                <div class="font-weight-bold">{{$rec->unload_placename}}</div>
                                            @endif
                                        </div>

                                    </div>

                                    <div class="row">

                                        <div class="form-group offset-md-4 col-md-2">
                                            <label for="mot_id" class="required">Оплата:</label>
                                            @if ($usrrights['edit'] )
                                                {!! Form::select('paytypeid', $rec->paytypes??[], old('paytypeid',$rec->paytypeid),
                                                 [
                                                     'id' => 'paytypeid',
                                                 'class' => 'form-control font-weight-bold',
                                                 'placeholder' => '-выбор-',
                                                 'required' => 'required',
                                                 ]) !!}
                                            @else
                                                <div
                                                    class="font-weight-bold">{{$rec->paytypes[$rec->paytypeid]??'?'}}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="name" class="required">Диспетчер:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3">
                                                <input type="text" name="disp_name" id="disp_name" required
                                                       class="ac_name disp_name form-control font-weight-bold"
                                                       value="{{$rec->dispatcher->name}}">
                                                <input type="text"
                                                       class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; " readonly>
                                                <input type="hidden" name="disp_staffid" id="disp_staffid"
                                                       class="ac_id staffid"
                                                       value="{{$rec->disp_staffid}}">
                                                <a class="btn btn-light id_lnk" data-id="disp_staffid"
                                                   data-obj="orgstaff"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->dispatcher->name}}</div>
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

                                <div class="row">

                                    <div class="form-group offset-md-3 col-md-2">
                                        <label for="name" class="required">Число рейсов:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="raid_qty" id="raid_qty" required
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
                                            <div class="font-weight-bold text-center">{{$$rec->raid_salary*$rec->raid_qty}}</div>
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
                                @if (1==1 and $rec->id != -1 and $usrrights['make_template']??true)
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
        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/mchn_raid_edit.js') }}" defer></script>

    @endif
@endsection
