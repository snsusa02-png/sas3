@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('machine_rqsts.index');
        header("Location:" . route('machine_rqsts.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

        <script src="{{ asset('js/callListOrgs.js') }}" defer></script>

        <?php
        $sysobjid = 483;
        $objcode = 'mchnrqsts';
        $rqstNum = ($rec->id == -1) ? ' (новая)' : $rec->id;
        $ThisTitle = "Заявка №$rqstNum";

        $route_index = route($objcode . '.index') . "?page=" . session($objcode . '_pageno') . '#' . $rec->id;
        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .this_rqst {
                background-color: #e1f0c6;
            }

            ::placeholder {
                color: silver;
                font-size: 0.75em;
            }
        </style>
        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-6">
                    <div class="card p-2 my-2 my-md-3">
                        <div class="card-header">
                            Заявка № <b>{{ ($rec->id==-1)?'-новая-':$rec->id}}</b>
                            <span class="ml-5 px-3 py-1"
                                  style="border: 1px solid white;border-radius: 14px;{{$rec->status_style}}"><span
                                    class="">статус:</span> <b>{{$rec->status_name}}</b></span>
                            <a class="btn btn-close btn-light btn-sm"
                               style="float:right;"
                               href="{{ $route_index }}"
                               title="Вернуться в список">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="card-body">

                            @if ($rec->statusid==0 and $errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div><br/>
                            @endif

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('mchnrqsts.update', $rec->id) }}">
                                @method('PUT')
                                @csrf

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="address">тип заявки:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('rqsttypeid', $rec->rqsttypes, $rec->rqsttypeid,
                                             [
                                             'id' => 'rqsttypeid',
                                             'class' => 'form-control',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->mchnrqsttype->name}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="address">дата заявки:</label>
                                        @if ($usrrights['set_init_at']??false)
                                            <input type="datetime-local" class="form-control text-center"
                                                   name="init_at"
                                                   value="{{$rec->init_at}}"/>
                                        @elseif($rec->init_at<>'')
                                            <div class="">{{date_format(date_create($rec->init_at),'d.m.Y')}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6  rt1_hide rt2_show rt3_hide">
                                        @if ($usrrights['save'])
                                            <label for="address">Необходимый вид техники:</label>
                                            {!! Form::select('rqstmchntypeid', $rec->mchntypes, $rec->rqstmchntypeid,
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             ]) !!}
                                        @elseif($rec->rqsttypeid==2)
                                            <label for="address">Необходимый вид техники:</label>
                                            <div class="font-weight-bold">{{$rec->mchntype->name}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group col-md-6 rt1_hide rt2_show rt3_hide">

                                        @if ($usrrights['save'])
                                            <label for="mchnrequirements">Требования к технике:</label>
                                            <input type="text" class="form-control" name="mchnrequirements"
                                                   value="{{old('mchnrequirements',$rec->mchnrequirements)}}"
                                                   placeholder="грузоподъемность, особенности"/>
                                        @elseif($rec->rqsttypeid==2)
                                            <label for="mchnrequirements">Требования к технике:</label>
                                            <div class="font-weight-bold">{{$rec->mchnrequirements}}</div>
                                        @endif
                                    </div>
                                </div>

                                @if ($usrrights['save'])
                                    <div class="row rt1_show rt2_hide rt3_hide">
                                        <div class="form-group col-md-12">
                                            <label for="machineid">Техника:</label>
                                            {!! Form::select('rqstmachineid', $rec->machines, $rec->rqstmachineid,
                                            [
                                            'class' => 'form-control',
                                            'placeholder' => '-выбор-',
                                            ]) !!}
                                        </div>
                                    </div>
                                @elseif($rec->rqsttypeid==1)
                                    <label for="machineid">Запрошенная техника:</label>
                                    <div class="font-weight-bold">{{$rec->rqstmachine->name}}</div>
                                @endif


                                <div class="row">
                                    <div class="form-group col-md-6  rt1_hide rt2_show rt3_hide">
                                        @if ($usrrights['save'])
                                            <label for="address">Объект:</label>
                                            {!! Form::select('buildobjid', $rec->buildobjs, $rec->buildobjid,
                                             [
                                             'id' => 'buildobjid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             ]) !!}
                                        @elseif($rec->rqsttypeid==2)
                                            <label for="address">Объект:</label>
                                            <div class="font-weight-bold">{{$rec->buildobj->name}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6  rt1_hide rt2_show rt3_hide">
                                        <label for="address">Вид работ:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('buildopertypeid', $rec->buildopertypes, $rec->buildopertypeid,
                                             [
                                             'id' => 'buildopertypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             ]) !!}
                                        @elseif($rec->rqsttypeid==2)
                                            <div class="font-weight-bold">{{$rec->buildopertype->name}}</div>
                                        @endif
                                    </div>
                                </div>

                                @if(1==0)
                                    <div class="row">
                                        <div class="form-group offset-md-3 col-md-9">
                                            <label for="orgcontractid" class="required">Подрядчик: Договор:</label>
                                            @if ($usrrights['save'] and isset($rec->orgcontracts))
                                                {!! Form::select('orgcontractid', $rec->orgcontracts, $rec->orgcontractid,
                                                 [
                                                 'id' => 'orgcontractid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '',
                                                 ]) !!}
                                            @else
                                                <div class="">
                                                    <b>{{$rec->org->name}}</b>
                                                    / {{$rec->contract->shortInfo}}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="form-group col-md-12  rt1_hide rt2_show rt3_hide">
                                        <label for="orgcontractid" class="required">Подрядчик: Договор
                                            @if(isset($rec->wrkcontractid))
                                                <a href="{{route("contracts.edit",$rec->wrkcontractid)}}"
                                                   target="_blank" id="wrkcontractid_link">
                                                    <i class="fa fa-external-link" aria-hidden="true"></i>
                                                </a>
                                            @endif
                                            :</label>
                                        @if (1==1 and $usrrights['save'] and isset($rec->orgcontracts))
                                            {!! Form::select('wrkorgcontractid', $rec->orgcontracts, $rec->wrkorgcontractid,
                                             [
                                             'id' => 'orgcontractid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             ]) !!}
                                        @else
                                            <div class="">
                                                <b>{{$rec->wrkorg->name}}</b>
                                                / {{$rec->wrkcontract->shortInfo}}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="form-group rt1_hide rt2_show rt3_hide">
                                    <label for="address">Адрес проведения работ:</label>
                                    @if ($usrrights['save'])
                                        <input type="text" class="form-control" name="tgt_addr" id="tgt_addr"
                                               value="{{old('tgt_addr',$rec->tgt_addr)}}"
                                               placeholder="Наименование, адрес объекта строительства"/>
                                    @elseif($rec->rqsttypeid==2)
                                        <div class="font-weight-bold">{{$rec->tgt_addr}}</div>
                                    @endif
                                </div>

                                <div class="row">
                                    @if ($usrrights['save'])
                                        <div class="form-group col-md-6">
                                            <label for="plnbegdt" class="rt1_show rt2_show rt3_hide">Начало:</label>
                                            <label for="plnbegdt" class="rt1_hide rt2_hide rt3_show small">дата и время
                                                подачи под погрузку:</label>
                                            <input type="datetime-local" class="form-control text-center"
                                                   name="plnbegdt"
                                                   value="{{old('plnbegdt',$rec->plnbegdt)}}"/>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="plnenddt" class="rt1_show rt2_show rt3_hide">Окончание:</label>
                                            <label for="plnenddt" class="rt1_hide rt2_hide rt3_show small">дата и время
                                                подачи под выгрузку:</label>
                                            <input type="datetime-local" class="form-control text-center"
                                                   name="plnenddt"
                                                   value="{{old('plnenddt',$rec->plnenddt)}}"/>
                                        </div>
                                    @else
                                        <div class="form-group col-md-6">
                                            @if($rec->rqsttypeid==3)
                                                <label class="small">дата и время подачи под погрузку:</label>
                                            @else
                                                <label for="name">Начало:</label>
                                            @endif
                                            <b>{{date_format(date_create($rec->plnbegdt),"d.m.Y H:i")}}</b>
                                        </div>
                                        <div class="form-group col-md-6">
                                            @if($rec->rqsttypeid==3)
                                                <label class="small">дата и время подачи под выгрузку:</label>
                                            @else
                                                <label>Окончание:</label>
                                            @endif
                                            <b>{{date_format(date_create($rec->plnenddt),"d.m.Y H:i")}} </b>
                                        </div>
                                    @endif
                                </div>

                                <div class="row">
                                    <div class="form-group rt1_hide rt2_show rt3_hide col-md-6">
                                        <label for="address">План. кол-во рабочих часов:</label>
                                        @if ($usrrights['save'])
                                            <input type="number" class="form-control" name="plnwrkhrs" id="plnwrkhrs"
                                                   value="{{old('plnwrkhrs',$rec->plnwrkhrs)}}"
                                                   min="0" step="1"
                                                   placeholder="Количество рабочих часов по плану"/>
                                        @else
                                            <div class="font-weight-bold">{{$rec->plnwrkhrs}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12  rt1_hide rt2_hide rt3_show">
                                        <label for="address">Грузоотправитель:</label>
                                        @if ($usrrights['save'])
                                            @if(1==0)
                                                {!! Form::select('src_orgid', $rec->src_orgs, old('src_orgid',$rec->src_orgid),
                                                 [
                                                 'id' => 'src_orgid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                            @else
                                                <div class="input-group mb-3 ">
                                                    {{ Form::hidden('src_orgid', old('src_orgid',$rec->src_orgid),['id'=>'src_orgid']) }}
                                                    <input type="text" class="form-control" name="src_orgname"
                                                           id="src_orgname"
                                                           value="{{old('src_orgname',$rec->src_orgname)}}"
                                                           style="height: 34px;"
                                                    />
                                                    <input type="text" class="form-control text-center small"
                                                           style="display: none; border: #d7f3e3;" id="ac_orgid"
                                                           readonly>
                                                    {{--													<div class="input-group-append">--}}
                                                    {{--														<a onclick="callListOrgs($('#orgid').val())" title="Поиск"--}}
                                                    {{--														   class="btn btn-sm btn-primary form-control">--}}
                                                    {{--															<i class="fa fa-search" aria-hidden="true"></i>--}}
                                                    {{--														</a>--}}
                                                    {{--													</div>--}}
                                                </div>
                                            @endif
                                        @else
                                            <div class="font-weight-bold">{{$rec->src_org->name}}</div>
                                        @endif
                                    </div>
                                </div>

                                @if ($usrrights['save'])
                                    <div class="row rt1_hide rt2_hide rt3_show">
                                        <div class="form-group col-md-12">
                                            <label for="src_addr" class="small">Адрес приема груза:</label>
                                            @if ($usrrights['save'])
                                                <input type="text" class="form-control" name="src_addr"
                                                       id="src_addr"
                                                       list="orgaddrs"
                                                       value="{{old('src_addr',$rec->src_addr)}}"
                                                       placeholder=""/>

                                                <datalist id="orgaddrs">
                                                    <option value="-адреса-">
                                                </datalist>

                                            @else
                                                <div class="font-weight-bold">{{$rec->src_addr}}</div>
                                            @endif
                                        </div>
                                        <div class="form-group col-md-12">
                                            <label for="tgt_addr" class="small">Адрес сдачи груза:</label>
                                            @if ($usrrights['save'])
                                                <input type="text" class="form-control" name="cargo_tgt_addr"
                                                       value="{{old('tgt_addr',$rec->tgt_addr)}}"
                                                       list="orgaddrs"
                                                       placeholder=""/>
                                            @elseif($rec->rqsttypeid==3)
                                                <div class="font-weight-bold">{{$rec->tgt_addr}}</div>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($rec->rqsttypeid==3)
                                    <div class="row ">
                                        <div class="form-group col-md-6">
                                            <label for="src_addr" class="small">Адрес приема груза:</label>
                                            <div class="font-weight-bold">{{$rec->src_addr}}</div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="tgt_addr" class="small">Адрес сдачи груза:</label>
                                            <div class="font-weight-bold">{{$rec->tgt_addr}}</div>
                                        </div>
                                    </div>
                                @endif


                                <div class="form-group">
                                    @if ($usrrights['save'])
                                        <label for="descript" class="rt1_show rt2_show rt3_hide">Описание работ,
                                            дополнительные сведения:</label>
                                        <label for="descript" class="rt1_hide rt2_hide rt3_show">Отгрузочное
                                            наименование
                                            груза:</label>
                                        <textarea class="form-control rounded-0" name="descript" id="descript"
                                                  rows="3">{{old('descript',$rec->descript)}}</textarea>
                                    @else
                                        @if($rec->rqsttypeid==3)
                                            <label class="small">Отгрузочное наименование груза:</label>
                                        @else
                                            <label>Описание работ, дополнительные сведения:</label>
                                        @endif
                                        <div><b>{{$rec->descript}}</b></div>
                                    @endif
                                </div>

                                @if ($usrrights['save'])
                                    <div class="row rt1_hide rt2_hide rt3_show">
                                        <div class="form-group col-md-12">
                                            <label for="cargo_boxcnt" class="small">Количество грузовых мест,
                                                маркировка,
                                                вид тары и
                                                способ упаковки:</label>
                                            @if ($usrrights['save'])
                                                <input type="text" class="form-control" name="cargo_boxcnt"
                                                       value="{{old('cargo_boxcnt',$rec->cargo_boxcnt)}}"
                                                       placeholder=""/>
                                            @else
                                                <div class="font-weight-bold">{{$rec->cargo_boxcnt}}</div>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($rec->rqsttypeid==3)
                                    <div class="row rt1_hide rt2_hide rt3_show">
                                        <div class="form-group col-md-12">
                                            <label for="cargo_boxcnt" class="small">Количество грузовых мест,
                                                маркировка,
                                                вид тары и
                                                способ упаковки:</label>
                                            <div class="font-weight-bold">{{$rec->cargo_boxcnt}}</div>
                                        </div>
                                    </div>
                                @endif


                                <div class="row">
                                    <div class="form-group col-md-9">
                                        <label for="name">Заказчик:</label>
                                        @if($usrrights['any_initorg']??false and $usrrights['save'])
                                            <div class="input-group mb-3 ">
                                                {{ Form::hidden('orgid', old('orgid',$rec->orgid),['id'=>'orgid']) }}
                                                <input type="text" class="form-control" name="orgname"
                                                       id="orgname"
                                                       value="{{old('orgname',$rec->org->name)}}"
                                                       style="height: 34px;"
                                                />
                                                <input type="text" class="form-control text-center small"
                                                       style="display: none; border: #d7f3e3;" id="ac_orgid"
                                                       readonly>
                                                <div class="input-group-append">
                                                    <a onclick="callListOrgs($('#orgid').val())" title="Поиск"
                                                       class="btn btn-sm btn-primary form-control">
                                                        <i class="fa fa-search" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->org->name}}
                                                {{ Form::hidden('orgid', old('orgid',$rec->orgid),['id'=>'orgid']) }}
                                            </div>
                                        @endif
                                    </div>
                                    @if($usrrights['setoffbalance']??false)
                                        <div class="form-group col-md-3 ">
                                            <label for="offbalance">Внебалансовая:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::checkbox('offbalance', 1, $rec->offbalance==1,
                                                 [
                                                 'class' => 'form-control text-warning',
                                                 ]) !!}
                                            @else
                                                <div
                                                    class="font-weight-bold">{!!  ($rec->offbalance==1)?'да':'нет'!!}</div>
                                            @endif

                                        </div>

                                    @endif
                                </div>

                                @if (isset($rec->contractid) and $rec->active==1)
                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="name">Договор:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('contractid', $rec->contracts, $rec->contractid,
                                                     [
                                                     'class' => 'form-control',
                                                     'placeholder' => '-выбор-',
                                                     ]) !!}
                                            @else
                                                <div
                                                    class="font-weight-bold">{{$rec->contract->getInfo($rec->contractid)}}</div>
                                            @endif
                                        </div>
                                    <!-- <div class="form-group col-md-6">
												<label for="address">Тип:</label>
												{!! Form::select('evnttypename', $rec->evnttypes, $rec->evnttypename,
											 	[
											 	'class' => 'form-control',
											 	'placeholder' => '-выбор-',
											 	]) !!}
                                        </div> -->
                                    </div>

                                @endif

                                <div class="row">
                                    @if ($usrrights['save'])
                                        <div class="form-group col-md-6">
                                            <label for="contactname" class="small">ФИО ответственного лица
                                                Заказчика:</label>
                                            <input type="text" class="form-control text-left"
                                                   name="contactname"
                                                   value="{{old('contactname',$rec->contactname)}}"/>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="contactphone" class="small">Телефон ответственного лица
                                                Заказчика:</label>
                                            <input type="text" class="form-control text-center"
                                                   name="contactphone"
                                                   value="{{old('contactphone',$rec->contactphone)}}"/>
                                        </div>
                                    @else
                                        <div class="form-group col-md-6">
                                            <label for="name" class="small">ФИО ответственного лица Заказчика:</label>
                                            <div class="font-weight-bold">{{$rec->contactname ??'-не указан-'}}</div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="name" class="small">Телефон ответственного лица
                                                Заказчика:</label>
                                            <div class="font-weight-bold">{{$rec->contactphone ??'-не указан-'}}</div>
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label for="descript" class="">Примечание:</label>
                                    @if ($usrrights['save'])
                                        <textarea class="form-control rounded-0" name="notes" id="notes"
                                                  rows="3">{{old('notes',$rec->notes)}}</textarea>
                                    @else
                                        <div><b>{{$rec->notes}}</b></div>
                                    @endif
                                </div>


                                @if (!$usrrights['save'] and isset($rec->inituserid))
                                    <div class="form-group col-md-12">
                                        <label for="name">Инициатор заявки:</label>
                                        <div>
                                            <b>{{$rec->inituser->name}}</b>, <span
                                                class=small>{{date_format(date_create($rec->init_at),"d.m.Y H:i:s")}}</span>:
                                        </div>
                                    </div>
                                @endif

                                <hr size=1>
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-outline-dark" style=''
                                            title="Сохранить как черновик">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif

                                @if ($usrrights['registrate']??false)
                                    <button type="submit" class="btn btn-warning" title="Сохранить и подать заявку"
                                            name="submit_key" value="registrate"
                                            onclick="return confirm('Передать заявку на согласование?')">
                                        <i class="fa fa-sign-in" aria-hidden="true"></i>
                                        Подать заявку
                                    </button>
                                @endif
                                @if ($usrrights['unregistrate']??false)
                                    <a class="btn btn-close btn-danger btn-sm"
                                       href="{{ route('mchnrqsts.unregistrate', $rec->id) }}"
                                       title="Отозвать заявку">
                                        <i class="fa fa-ban" aria-hidden="true"></i>
                                        Отозвать заявку
                                    </a>
                                @endif

                                <a class="btn btn-close btn-info btn-sm ml-2" href="{{ $route_index }}"
                                   title="Вернуться в список">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>

                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route('mchnrqsts.delete', $rec->id)}}"
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
                                            formaction="{{ route('mchnrqsts.admindelete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Заявка будет удалена административно - без учета ограничений!\n\nПродолжать?')"
                                            title="Административно удалить заявку"
                                    >
                                        <i class="fa fa-bomb" aria-hidden="true"></i>
                                    </button>
                                @endif
                                @if ($usrrights['save_as']??false)
                                    <a class="btn btn-close btn-outline-dark btn-sm"
                                       href="{{ route('mchnrqsts.copy', $rec->id) }}"
                                       title="Сохранить как копию">
                                        <i class="fa fa-files-o" aria-hidden="true"></i> Копия
                                    </a>
                                @endif

                                {{--                                @if ($rec->active == 1 and $rec->decision??1==1 and $rec->rqsttypeid==2)--}}
                                @if ($rec->active == 1 and $rec->decision??1==1)
                                    <a class="btn btn-close btn-light ml-3 btn-sm float-right"
                                       href="{{ route('mchnrqsts.print1', $rec->id) }}"
                                       target="_blank"
                                       title="Напечатать заявку">
                                        <i class="fa fa-print" aria-hidden="true"></i>
                                        Заявка
                                    </a>
                                @endif



                                @if ($rec->id != -1)
                                    <div class="small justify-content-center" style="margin-top: 8px; color:gray;">
                                        <hr size="1">
                                    <!-- создана: {{$rec->created_at}} / {{$rec->whocrt->name}}
                                        <br>
                                        изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}}<br> -->
                                        <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,
										'route'=>Route::current()->getName()])}}" class="ml-1">журнал</a>
                                    </div>
                            @endif
                            <!-- </form> -->
                        </div>
                    </div>
                </div>


                @if (1==1 and isset($rec))
                    <div class="col-md-6 mt-3">

                        @include('mchnrqsts._showfact')

                        @include('mchnrqsts._approve')
                        @include('mchnrqsts._showdecision')

                        @include('mchnrqsts._facts')
                        @include('mchnrqsts._setfact')

                        @include('obj_readers/_readers')




                        @if(($rec->statusid==0 or $rec->statusid==1) and isset($rqsts))

                            <div class="card">
                                <div class="card-header">Активные заявки на "<b>{{$rec->rqstmachine->name}}</b>"</div>
                                <div class="card-body">

                                    @if(isset($rqsts) and $rqsts->count()>0)
                                        <table class="table table-striped small">
                                            <thead>
                                            <tr>
                                                <td>#</td>
                                                <td class="text-center " nowrap>Когда (план)</td>
                                                <td class="text-center">Прододжительность, чч:мм</td>
                                                <td>Описание</td>
                                            </tr>

                                            </thead>
                                            <tbody>
                                            <?php
                                            $rec0 = 1;
                                            $totDrctSum = 0;
                                            $totNaclSum = 0;
                                            $totSPSum = 0;
                                            $totSum = 0;
                                            ?>
                                            @foreach($rqsts as $itm)
                                                <?php
                                                $plnbegdt = strtotime($itm->plnbegdt);
                                                $plnbegdt_c = date("Y-m-d,  H:i", $plnbegdt);

                                                $plnenddt = strtotime($itm->plnenddt);
                                                if (date("Y-m-d", $plnenddt) == date("Y-m-d", $plnbegdt))
                                                    $plnenddt_c = date("H:i", $plnenddt);
                                                else
                                                    $plnenddt_c = date("Y-m-d H:i", $plnenddt);

                                                if ($itm->id == $rec->id)
                                                    $lineClass = 'this_rqst';
                                                else
                                                    $lineClass = '';
                                                ?>
                                                <tr class="{{$lineClass}}">
                                                    <td class="small text-right">
                                                        {{$loop->index + $rec0}}
                                                    </td>
                                                    <td class="text-left small">
                                                        {{$plnbegdt_c}} - {{$plnenddt_c}}
                                                    </td>
                                                    <td class="text-center">
                                                        {{mb_substr($itm->duration,0,5)}}
                                                    </td>
                                                    <td>
                                                        {{$itm->name}}: {{$itm->descript}}
                                                    </td>
                                                </tr>
                                                <?php
                                                $totSum += $itm->docsum;
                                                $totDrctSum += $itm->drct_sum;
                                                $totNaclSum += $itm->nacl_sum;
                                                $totSPSum += $itm->sp_sum;
                                                ?>
                                            @endforeach
                                            </tbody>
                                            <tfoot>
                                            </tfoot>
                                        </table>
                                    @else
                                        <div class="text-center font-weight-bold">Нет согласованных заявок</div>
                                    @endif
                                </div>
                            </div>

                            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
                            <style>
                                #chart_div {
                                    margin: 0 auto;
                                }
                            </style>

                            @if(isset($rqsts) and $rqsts->count()>0)
                                <div class="container">
                                    <div class="row">
                                        <div class="card col-md-12 mt-3" style="margin: 0 auto;">
                                            <div class="card-header">График занятости для
                                                "<b>{{$rec->rqstmachine->name}}</b>"
                                            </div>
                                            <div class="card-body">
                                                <div id="chart_div" align="center" class=""></div>
                                            </div>
                                            <div class="card-footer"></div>
                                        </div>
                                    </div>
                                </div>

                                <script type="text/javascript">
                                    google.charts.load('current', {'packages': ['timeline']});
                                    google.charts.setOnLoadCallback(drawChart);

                                    function drawChart() {

                                        var dataTable = new google.visualization.DataTable();

                                        dataTable.addColumn({type: 'string', id: 'RowLabel'});
                                        dataTable.addColumn({type: 'string', id: 'Name'});
                                        // dataTable.addColumn({ type: 'string', role: 'tooltip' });
                                        dataTable.addColumn({type: 'date', id: 'Start'});
                                        dataTable.addColumn({type: 'date', id: 'End'});
                                        dataTable.addRows([
                                                @foreach($rqsts as $itm)
                                            ['{{$itm->evnttypename}}', '{{$itm->descript}}',
                                                new Date( {{date("Y, m, d, H, i", strtotime($itm->plnbegdt.' -1 month')) }} ),
                                                new Date( {{date("Y, m, d, H, i", strtotime($itm->plnenddt.' -1 month')) }} )],
                                            @endforeach
                                        ]);

                                        var options = {
                                            title: 'Rate the Day on a Scale of 1 to 10',
                                            legend: 'asdasdasdas none',
                                            // height: 650,
                                            timeline: {
                                                groupByRowLabel: true,
                                                showRowLabels: true,
                                                //singleColor: '#8d8'
                                            },
                                            // backgroundColor: '#ffd'
                                        };

                                        var chart = new google.visualization.Timeline(document.getElementById('chart_div'));

                                        chart.draw(dataTable, options);
                                    }

                                </script>
                            @endif
                    </div>
                @endif

                @endif

                @if (1==1 and $rec->id != -1)
                    <div class="col-md-6">
                        <?php $FlagsHeader = "Флаги объекта" ?>
                        @include('objflags/objflags')
                    </div>

                    @endif

                    </form>

            </div>
        </div>

        <script src="{{ asset('js/mchnrqst_edit.js') }}" defer></script>
    @endif

@endsection
