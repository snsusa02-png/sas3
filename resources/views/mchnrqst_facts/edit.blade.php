@extends('layouts.edit')

@section('content')
    @if (!isset($rec))
        <?php
        redirect()->route('mchnrqsts.index');
        header("Location:" . route('mchnrqsts.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 485;
        $thisSysObjId = $sysobjid;
        $sysobjcode = 'mchnrqst_facts';
        $objcode = 'mchnrqst_facts';
        $ThisTitle = "Время работы спецтехники";

        //$route_index = route('qchecks.edit', $rec->rqstid);
        //$route_index = route('mchnrqsts.index') . '#' . $rec->id;
        $route_index = route('mchnrqsts.edit', $rec->rqstid) . '#item_' . $rec->id;


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
                <div class="col-md-7">
                    <div class="card p-2 my-2 my-md-3" style="background-color: #f8f8f8">
                        <div class="card-header">
                            <?php
                            //$chkdate = $rec->jobtimesheet->docdate;
                            //$chkdate = date_format(date_create($rec->jobtimesheet->docdate), 'd.m.Y');
                            ?>
                            {{$ThisTitle}} "<b>{{$rec->rqst->buildobj->name}}</b>"
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
                                {{ Form::hidden('rqstid', $rec->rqstid) }}
                                {{--                                {{ Form::hidden('buildobjid', $rec->jobtimesheet->buildobjid, ['id'=>'buildobjid']) }}--}}
                                {{ Form::hidden('ttt', 0) }}

                                @if($rec->rqst->isowncarrier)

                                    @if($rec->rqst->paytypeid==1)

                                        <div class="form-group asgn_own">
                                            <label for="name">Договор с заказчиком:</label>
                                            {!! Form::select('contractid', $rec->contracts, $rec->contractid,
                                                [
                                                'class' => 'form-control',
                                                'placeholder' => '-выбор-',
                                                ]) !!}
                                        </div>

                                    @elseif($rec->rqst->paytypeid==2)
                                        <div class="row">
                                            <div class="form-group offset-md-6 col-md-3">
                                                <label for="fctenddt" class="small">Цена, &#8381;/час:</label>
                                                <input type="number" class="form-control text-right"
                                                       name="fct_price" min="0" id="fct_price"
                                                       value="{{old('fct_price',$rec->fct_price)}}"
                                                />
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="fctenddt" class="small">Сумма, &#8381;:</label>
                                                <input type="number" class="form-control text-right"
                                                       name="fct_sum" id="fct_sum" readonly
                                                       value="{{old('fct_sum',$rec->fct_sum)}}"
                                                />
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="form-group asgn_own col-md-6 ">
                                            <label for="name">суб-перевозчик:</label>
                                            {!! Form::select('driver_orgid', $rec->drivers, $rec->driver_orgid,
                                                [
                                                'id' => 'driver_orgid',
                                                'class' => 'form-control',
                                                'placeholder' => '-выбор-',
                                                ]) !!}
                                        </div>
                                        <div class="form-group asgn_own col-md-6">
                                            <label for="name">Водитель:</label>

                                            <input type="text" list="driverorgstaff" name="drivername"
                                                   id="drivername"
                                                   class="form-control text-left"
                                                   value="{{old('drivername',$rec->drivername)}}"/>

                                            <datalist id="driverorgstaff">
                                                <option value="-ФИО водителя-">
                                            </datalist>
                                        </div>
                                    </div>


                                @else
                                    {{--Сторонний перевозчик--}}
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="fctbegdt" class="small">Ед. измерения:</label>
                                            {!! Form::select('fct_priceunit', $rec->priceunits, $rec->fct_priceunit,
                                                                    [
                                                                    'id' => 'fct_priceunit',
                                                                    'class' => 'form-control',
                                                                    'placeholder' => '-выбор-',
                                                                    ]) !!}
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="fctenddt" class="small">Количество, ЕИ:</label>
                                            <input type="number" class="form-control text-right"
                                                   name="fct_qty" min="0" step="0.1"
                                                   value="{{old('fct_qty',$rec->fct_qty)}}"
                                            />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="fctenddt" class="small">Цена, &#8381;/ЕИ:</label>
                                            <input type="number" class="form-control text-right"
                                                   name="fct_price" min="0"
                                                   value="{{old('fct_price',$rec->fct_price)}}"
                                            />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="fctenddt" class="small">Сумма, &#8381;:</label>
                                            <input type="number" class="form-control text-right"
                                                   name="fct_sum" readonly
                                                   value="{{old('fct_sum',$rec->fct_sum)}}"
                                            />
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="fctbegdt" class="small">Наименование использованной техники:</label>
                                            <input type="text" class="form-control text-left"
                                                   name="asgnmachinename"
                                                   value="{{old('asgnmachinename',$rec->asgnmachinename)}}"/>
                                        </div>
                                    </div>

                                @endif

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="name" class="required">Техника:</label>
                                        @if ($usrrights['save'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="machine_name"
                                                       class="machine_name form-control"
                                                       value="{{old('machine_name',$rec->machine->name)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="machineid" class="machineid" id="machineid"
                                                       value="{{old('machineid',$rec->machineid)}}">
                                                <input type="hidden" name="orgid" class="orgid" id="orgid"
                                                       value="{{old('orgid',$rec->orgid)}}">
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->machine->name}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-5">
                                        <label class="required">Начало работы</label>
                                        @if ($usrrights['save'])
                                            <input type="datetime-local"
                                                   class="form-control text-right font-weight-bold"
                                                   id="fctbegdt" name="fctbegdt"
                                                   value="{{old('fctbegdt',$rec->fctbegdt)}}"
                                            />
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->fctbegdt}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label>Окончание</label>
                                        @if ($usrrights['save'])
                                            <input type="datetime-local"
                                                   class="form-control text-right font-weight-bold"
                                                   id="fctenddt" name="fctenddt" max="{{now()}}"
                                                   value="{{old('fctenddt',$rec->fctenddt)}}"
                                            />
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->endtime}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-8 col-md-4">
                                        <label>Продолжительность</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="fcthrs" id="fcthrs"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->fcthrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->fcthrs}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="buildopertypeid" class="required">Занят в работе:</label>
                                        @if ($usrrights['save'] )
                                            {!! Form::select('buildopertypeid'
, $rec->buildopertypes
, old('buildopertypeid',$rec->buildopertypeid),
                                             [
                                                 'id' => 'buildopertypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->buildopertype->name??'-'}}</div>
                                        @endif
                                    </div>
                                </div>

                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group offset-md-3 col-md-9">
                                            <label for="orgcontractid">Подрядчик: Договор:</label>
                                            @if ($usrrights['save'] and isset($rec->orgcontracts))
                                                {!! Form::select('orgcontractid', $rec->orgcontracts, $rec->orgcontractid,
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

                                @if(1==0)
                                    <div class="row">
                                        <div class="offset-md-3 col-md-4">
                                            <div class="form-group">
                                                <label for="category">Отметка (уровень):</label>
                                                @if ($usrrights['save'] )
                                                    <input type="text" class="form-control rounded-0 font-weight-bold"
                                                           name="objlvl"
                                                           id="objlvl"
                                                           value="{{old('objlvl',$rec->objlvl)}}">
                                                @else
                                                    <div class="font-weight-bold">
                                                        <div class="font-weight-bold">{{$rec->objlvl??'-'}}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="offset-md-0 col-md-5">
                                            <div class="form-group">
                                                <label for="category">Оси:</label>
                                                @if ($usrrights['save'] )
                                                    <input type="text" class="form-control rounded-0 font-weight-bold"
                                                           name="objaxis"
                                                           id="objaxis"
                                                           value="{{old('objaxis',$rec->objaxis)}}">
                                                @else
                                                    <div class="font-weight-bold">
                                                        <div class="font-weight-bold">{{$rec->objaxis??'-'}}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif


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
                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group offset-md-1 col-md-5">
                                            <label for="active" style="color: rgb(73, 80, 87);">Готовность:
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
                                                     ]) !!}
                                            @else
                                                <div class="font-weight-bold">{{$activetypes[$rec->active]??'?'}}</div>
                                            @endif

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
                                @if ($rec->id != -1)
                                    <div class="small" style="margin-top: 8px; color:gray;">
                                        создана: {{$rec->created_at}} / {{$rec->whocrt->short_fio()}}
                                        &nbsp;
                                        изменена: {{$rec->updated_at}} / {{$rec->whoupd->short_fio()}}
                                        <br><a
                                            href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                    </div>
                                @endif
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
        <script src="{{ asset('js/mchnrqst_facts_edit.js') }}" defer></script>

    @endif
@endsection
