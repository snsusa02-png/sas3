@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('mchn_raids.index');
        header("Location:" . route('mchn_raids.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 1107;
        $thisSysObjId = $sysobjid;
        $sysobjcode = 'mr_opers';
        $objcode = $sysobjcode;
        $ThisTitle = "Операция";

        $retURL = \Request::get('returl') ?? $rec->retURL ?? (route('mchn_raids.edit', $rec->mr_id) . "#oper_" . $rec->id);

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
                            <i class="fa fa-shopping-cart text-primary" aria-hidden="true"></i>
                            {{$ThisTitle}} "<b>{{$rec->mchn_raid->info}}</b>"
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
                                  action="{{ route($sysobjcode.'.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('mr_id', $rec->mr_id) }}
                                {{ Form::hidden('wrkdate', $rec->mchn_raid->wrkdate,['id'=>'wrkdate']) }}
                                {{ Form::hidden('opertypeid', $rec->mchn_raid->opertypeid,['id'=>'opertypeid']) }}
                                {{ Form::hidden('ttt', 0) }}

                                <div class="row">

                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="mot_id" class="required">Тип операции:</label>
                                        @if ($usrrights['edit'] )
                                            {!! Form::select('sale_dir', $rec->sale_dirs??[], old('sale_dir',$rec->sale_dir),
                                             [
                                                 'id' => 'sale_dir',
                                             'class' => 'form-control font-weight-bold',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->sale_dirs[$rec->sale_dir]??'?'}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-8">
                                        <label for="name" class="">Пояснение:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group">
                                                <input type="text" class="form-control text-secondary"
                                                       name="name" id="name"
                                                       maxlength="60"
                                                       value="{{$rec->name}}"
                                                       autocomplete="on"
                                                />
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->name}}</div>
                                        @endif
                                    </div>
                                    {{--                                    <div class="col-md-2">--}}
                                    {{--                                        <label for="name" class="" title="Очередность операции">Порядок:</label>--}}
                                    {{--                                        <div class="input-group">--}}
                                    {{--                                            <input type="number" value="{{$rec->ordr}}" min="1" max="9" class="form-control text-right">--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                </div>


                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-5">
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
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-7">
                                        <label for="name" class="required"><span id="lbl_sup">Поставщик</span>:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="suporg_name" required id="suporg_name"
                                                       class="ac_name ac_suporg_name form-control font-weight-bold"
                                                       value="{{old('suporg_name',$rec->suporg->info)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       title=""
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="suporgid" class="ac_id" id="suporgid"
                                                       data-gk="{{$rec->sup_gk}}"
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

                                    <div class="form-group offset-md-0 col-md-5 sup_place_info">
                                        <label for="name" class=""><span id="lbl_sup_place">Место</span>:</label>
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
                                                {!! Form::select('sup_placeid', $rec->sup_places??[],
                                                    old('sup_placeid',$rec->sup_placeid),
                                                    [
                                                    'id' => 'sup_placeid',
                                                    'class' => 'form-control small',
                                                    'placeholder' => '',
                                                    ]) !!}
                                            </div>

                                        @else
                                            <div class="font-weight-bold">{{$rec->sup_placename}}</div>
                                        @endif
                                    </div>

                                </div>

                                <div class="row">

                                    <div class="form-group offset-md-0 col-md-12">
                                        <label for="name" class="required"><span id="lbl_refitm">Товар / Услуга</span>:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group">
                                                <input type="text" class="form-control font-weight-bold ac_name "
                                                       name="itm_name" id="itm_name" required
                                                       maxlength="60"
                                                       value="{{$rec->refitem->name}}"
                                                       autocomplete="off"
                                                />
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3;" readonly>
                                                <input type="hidden" name="refitmid" id="refitmid"
                                                       class="ac_id"
                                                       value="{{$rec->refitmid}}">
                                                <input type="hidden" name="producttypeid" id="producttypeid"
                                                       value="{{$rec->refitem->producttypeid}}">
                                                <a class="btn btn-light id_lnk" id="refitmid_lnk"
                                                   data-id="refitmid"
                                                   data-obj="refitems" target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->refitem->name}}</div>
                                        @endif
                                    </div>

                                </div>

                                <div class="row">

                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="required">Количество,
                                            <span class="font-weight-bold"
                                                  id="load_qty_unit">{{$rec->refitem->unittype->name??'ЕИ'}}</span>:
                                        </label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="itm_qty" id="itm_qty"
                                                       class="form-control text-right font-weight-bold" required
                                                       min="0" step="0.001" max="999"
                                                       value="{{old('itm_qty',$rec->itm_qty)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->itm_qty}} {{$rec->qty_unit}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-3">
                                        <label for="name" class="required">Цена, &#8381;:</label>
                                        @if ($usrrights['edit'])
                                            <?php
                                            $readonly = ($rec->sup_gk == 0) ? 'readonly' : '';
                                            ?>
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="itm_price" id="itm_price"
                                                       class="form-control text-right font-weight-bold" required
                                                       min="0" step="0.01" {{$readonly}}
                                                       value="{{old('itm_price',$rec->itm_price)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{number_format($rec->itm_price,2)}}</div>
                                        @endif
                                    </div>


                                    <div class="form-group col-md-3">
                                        <label>Стоимость, &#8381;</label>
                                        @if ($usrrights['edit'])
                                            <input type="text" name="itm_sum" id="itm_sum" required
                                                   class="form-control text-center font-weight-bold"
                                                   readonly value="{{$rec->itm_sum}}">
                                        @else
                                            <div
                                                class="font-weight-bold text-center">{{number_format($rec->itm_sum,2)}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-3">
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
                                                class="font-weight-bold text-center">{{$rec->paytypes[$rec->paytypeid]??'?'}}</div>
                                        @endif
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-7">
                                        <label for="name" class="required"><span id="lbl_org">Заказчик</span>:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="org_name" required id="org_name"
                                                       class="ac_name ac_org_name form-control font-weight-bold"
                                                       value="{{old('org_name',$rec->org->info)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       title=""
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="orgid" class="ac_id" id="orgid"
                                                       data-gk="{{$rec->org_gk}}"
                                                       value="{{old('orgid',$rec->orgid)}}">
                                                <a class="btn btn-light id_lnk" data-id="orgid" data-obj="orgs"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                            <div></div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->org->info}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-5">
                                        <label for="name" class=""><span id="lbl_org_place">Место</span>:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="org_placename" id="org_placename"
                                                       class="ac_name org_placename form-control font-weight-bold"
                                                       value="{{old('org_placename',$rec->org_placename)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="org_placeid" class="org_placeid ac_id"
                                                       id="org_placeid"
                                                       value="{{old('org_placeid',$rec->org_placeid)}}">
                                                <a class="btn btn-light id_lnk" data-id="org_placeid" data-obj="places"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                            {{--                                            <div class="input-group">--}}
                                            {{--                                                {!! Form::select('org_placeid', $rec->org_places??[],--}}
                                            {{--                                                    old('org_placeid',$rec->org_placeid),--}}
                                            {{--                                                    [--}}
                                            {{--                                                    'id' => 'org_placeid',--}}
                                            {{--                                                    'class' => 'form-control small',--}}
                                            {{--                                                    'placeholder' => '',--}}
                                            {{--                                                    ]) !!}--}}
                                            {{--                                            </div>--}}

                                        @else
                                            <div class="font-weight-bold">{{$rec->org_placename}}</div>
                                        @endif
                                    </div>

                                </div>

                                <div class="row">

                                    <div class="form-group offset-md-0 col-md-6">
                                        <label for="name" class="">Договор:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('contractid', $rec->contracts??[], old('contractid',$rec->contractid),
                                             [
                                                 'id' => 'contractid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             ]) !!}
                                        @else
                                            <input type="text" class="form-control" readonly
                                                   value="{{$rec->contract->info}}"
                                            />
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-1 col-md-3 raid_info">
                                        <label for="name" class="required" id="lbl_raid_qty">Кол-во рейсов:</label>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="raid_qty" id="raid_qty"
                                                       class="form-control text-right font-weight-bold"
                                                       min="0" step="1" max="99"
                                                       value="{{old('raid_qty',$rec->raid_qty)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->raid_qty}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-2 col-md-4 agent_sum_info">
                                        <label for="name" class="">Вознаграждение агента, &#8381;:</label>
                                        @if ($usrrights['edit'])
                                            <?php
                                            //$readonly = ($rec->sup_gk == 0) ? 'readonly' : '';
                                            ?>
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="agent_sum" id="agent_sum"
                                                       class="form-control text-right font-weight-bold"
                                                       min="0" step="0.01"
                                                       value="{{old('agent_sum',$rec->agent_sum)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{number_format($rec->agent_sum,2)}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-2 col-md-4 driver_sum_info">
                                        <label for="name" class="">ЗП водителя, &#8381;:</label>
                                            <?php
                                            $readonly = (1==0) ? 'readonly' : '';
                                            ?>
                                        @if ($usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="number" name="driver_sum" id="driver_sum"
                                                       class="form-control text-right font-weight-bold"
                                                       {{$readonly}} step="0.01"
                                                       value="{{old('driver_sum',$rec->driver_sum)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{number_format($rec->driver_sum,2)}}</div>
                                        @endif
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
                                <a class="btn btn-close btn-info" href="{{ $retURL }}"
                                   title="Вернуться">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <a href="{{ route($objcode.'.delete', $rec->id)}}"
                                       class="btn btn-danger btn-sm ml-3"
                                       onclick="return confirm('Вы действительно хотите удалить запись?')"
                                       title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </a>
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
                        @include('mr_opers.linked_paydocs')
                        {{--                        @include('obj_readers._readers')--}}
                    </div>
                @endif
            </div>

            @if($rec->id<>-1)
                <div class="row">
                    <div class="col-md-8">
                        @include('obj_finopers._finopers')
                    </div>
                </div>
            @endif

        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/mr_oper_edit.js') }}" defer></script>

    @endif
@endsection
