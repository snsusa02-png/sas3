@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
            <?php
            redirect()->route('fuelcard_pays.index');
            header("Location:" . route('fuelcard_pays.index'));
            die();
            ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

            <?php
            $sysobjid = 562;
            $thisSysObjId = $sysobjid;
            $thisSysObjCode = 'fuelcard_pays';
            $sysobjcode = $thisSysObjCode;
            $ThisTitle = "Регистрация операции по топливной карте";

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
                <div class="col-md-8">
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
                                {{ Form::hidden('in_gk', $rec->in_gk,['id'=>'in_gk']) }}
                                {{ Form::hidden('suporgid', $rec->card->suporgid,['id'=>'suporgid']) }}
                                {!! Form::hidden('returl', $retURL) !!}

                                <div class="row">

                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="required">Дата:</label>
                                        @if ($usrrights['edit'])
                                            <input type="date" class="form-control text-center font-weight-bold"
                                                   name="paydate" id="paydate" required
                                                   min="{{$rec->paydate_min}}"
                                                   max="{{today()->format('Y-m-d')}}"
                                                   value="{{old('paydate',$rec->paydate)}}"/>
                                        @else
                                            <div
                                                class="font-weight-bold text-center">{{date_create($rec->paydate)->format('d.m.Y')}}
                                                {{ Form::hidden('paydate', $rec->paydate,['id'=>'paydate']) }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="required">Топливная карта:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('cardid', $rec->cards??[], old('cardid',$rec->cardid),
                                             [
                                                 'id' => 'cardid',
                                             'class' => 'form-control font-weight-bold',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->card->name}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-6">
                                        <label for="name" class="required0">Предложения поставщика топлива:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('ri_sup_priceid', $rec->sup_prices??[], old('ri_sup_priceid',$rec->ri_sup_priceid),
                                             [
                                                 'id' => 'ri_sup_priceid',
                                             'class' => 'form-control font-weight-bold',
                                             'placeholder' => '-выбор-',
                                             'required0' => 'required0',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->ri_sup_priceid??'---'}}</div>
                                        @endif
                                        {{ Form::hidden('refitmid', $rec->refitmid,['id'=>'refitmid']) }}
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-3 col-md-3">
                                        <label for="name" class="required" id="lbl_raid_qty">Объем топлива, л:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="fuel_qty" id="fuel_qty"
                                                       class="form-control text-right font-weight-bold"
                                                       min="0" step="0.1"
                                                       value="{{old('fuel_qty',$rec->fuel_qty)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->fuel_qty}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="required0" title="Цена за единицу измерения (литр)">Цена,
                                            &#8381;:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="fuel_price" id="fuel_price" required
                                                       title="Цена за единицу измерения"
                                                       class="form-control text-right font-weight-bold"
                                                       min="0" step="0.000001"
                                                       value="{{old('fuel_price',$rec->fuel_price)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->fuel_price}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="required" title="Общая стоимость">Стоимость,
                                            &#8381;:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="paysum" id="paysum" required
                                                       title="Общая стоимость топлива"
                                                       class="form-control text-right font-weight-bold"
                                                       min="0" step="0.01"
                                                       value="{{old('paysum',$rec->paysum)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->paysum}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="form-group offset-md-3 col-md-9">
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
                                </div>

                                <div class="row">

                                    @if(1==0)
                                        <div class="form-group col-md-6 driver_info" style="{{$in_gk_hide}}">
                                            <label for="name" class="">Водитель: </label>
                                            {{--                                        @if ($usrrights['edit_dmd'])--}}
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="drivername" id="drivername"
                                                           class="driver_name form-control ac_name font-weight-bold"
                                                           value="{{old('drivername',$rec->driver->name)}}">
                                                    <input type="text" class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3; max-width: 30px"
                                                           readonly>
                                                    <input type="hidden" name="driverid" class="ac_id" id="driverid"
                                                           value="{{old('driverid', $rec->driverid)}}">
                                                    <a class="btn btn-light id_lnk" id="driverid_lnk"
                                                       data-id="driverid" data-obj="orgstaff" target="_blank">
                                                        <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->card->name}}</div>
                                                <input type="hidden" name="driverid" id="driverid"
                                                       value="{{$rec->driverid}}">
                                            @endif
                                        </div>
                                    @endif

                                    <div class="offset-md-3 col-md-9">
                                        <div class="form-group">
                                            <label for="decision">Примечание:</label>
                                            @if ($usrrights['edit'])
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
                                @if ($usrrights['save'])
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

                </div>
                @if($rec->id<>-1)
                    <div class="col-md-2">
                        @include('objfiles.obj_files')
                        {{--                        @include('obj_readers._readers')--}}
                    </div>
                @endif
            </div>

            @if(1==0 and $rec->id<>-1)
                <div class="row">
                    <div class="col-md-10">
                        @include('obj_finopers._finopers')
                    </div>

                </div>
            @endif
        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/fuelcard_pay_edit.js') }}" defer></script>

    @endif
@endsection
