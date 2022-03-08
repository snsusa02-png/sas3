@extends('layouts.edit')

@section('content')
    <?php
    $sysobjid = 915;
    $thisSysObjId = 915;
    $sysobjcode = 'invoices';
    $thisTitle = "Документ";
    $userid = \Auth::user()->id;

    if (!isset($rec)) {
        redirect()->route($sysobjcode . '.index');
        header("Location:" . route($sysobjcode . '.index'));
        die();
    }

    $retRoute = ($rec->retURL)
        ? ($rec->retURL . '#invoices')
        : route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;


    $doctypes = [1 => 'счет', 2 => 'УПД'];

    if ($rec->doctypeid == 1) {
        $retRoute = ($rec->retURL)
            ? ($rec->retURL . '#invoices')
            : route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;
        $thisTitle = "Счет";
        $shortDocName = 'счета';
        $cardhdr_bgcol = '#c6eaf7';
        $cardbody_bgcol = '#eefbfd';
        $docsum_readonly = '';
    } else {
        if (isset($rec->pardocid))
            $retRoute = ($rec->retURL)
                ? ($rec->retURL . '#invoices')
                : route($sysobjcode . '.edit', $rec->pardocid);

        $thisTitle = "УПД";
        $shortDocName = 'УПД';
        $cardhdr_bgcol = '#fbf1d0';
        $cardbody_bgcol = '#fbfbd6';
        //$docsum_readonly = 'readonly'; //на самом деле будет пересчитана по факту использования позиций
        $docsum_readonly = '';

    }
    $c7_css = ($rec->categoryid == 7) ? '' : 'display:none';
    //    dd($cardbody_bgcol);

    if ($usrrights['save'] ?? false) {
        $readonly = '';
    } else {
        $readonly = 'readonly';
        $docsum_readonly = 'readonly';
    }
    ?>

    <script src="{{ asset('js/collapse.js') }}" defer></script>

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
                <div class="card p-2 my-2 my-md-3">
                    <div class="card-header" style="background-color: {{$cardhdr_bgcol}}">
                        {{$thisTitle}}
                        <a class="btn btn-close btn-light btn-sm"
                           style="float:right;"
                           href="{{ $retRoute }}"
                           title="Вернуться в список">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="card-body" style="background-color: {{$cardbody_bgcol}}">

                        @include('layouts.err_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', $rec->id) }}"
                              enctype="multipart/form-data">
                            @method('PUT')
                            @csrf
                            {{ Form::hidden('ttt', 1) }}
                            {{ Form::hidden('pardocid', $rec->pardocid) }}
                            {{ Form::hidden('doctypeid', $rec->doctypeid) }}
                            {{ Form::hidden('returl', $retRoute) }}
                            {{ Form::hidden('lnk_pardocid', $rec->lnk_pardocid) }}
                            {{ Form::hidden('uid', $userid,['id'=>'uid']) }}


                            @if(isset($rec->pardocid))
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="address">Родительский документ:</label>
                                        <div><a href="{{route("invoices.edit",$rec->pardocid )}}">
                                                {{$doctypes[$rec->pardoc->doctypeid]??'?'}} № {{$rec->pardoc->docnum}}
                                                от {{$rec->pardoc->docdate}}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="row">
                                <div class="form-group col-md-4 col-sm-6">
                                    <label for="docnum" class="required">№ {{$shortDocName}}:</label>
                                    @if ($usrrights['save']??false)
                                        <input type="text" class="form-control" name="docnum"
                                               value="{{old('docnum',$rec->docnum)}}"/>
                                    @else
                                        <input type="text" class="form-control" name="docnum" readonly
                                               value="{{old('docnum',$rec->docnum)}}"/>
                                    @endif
                                </div>
                                <div class="form-group col-md-4 col-sm-6">
                                    <label for="docnum" class="required">Дата {{$shortDocName}}:</label>
                                    @if ($usrrights['save']??false)
                                        <input type="date" class="form-control" name="docdate"
                                               {{--                                               min="{{today()->modify('-30 day')->format('Y-m-d')}}"--}}
                                               max="{{today()->format('Y-m-d')}}"
                                               value="{{old('docdate',$rec->docdate)}}"/>
                                    @else
                                        <input type="date" class="form-control" name="docdate"
                                               readonly
                                               value="{{$rec->docdate}}"/>
                                    @endif
                                </div>

                                @if($rec->doctypeid==1)
                                    <div class="form-group col-md-4 col-sm-6">
                                        <label for="docnum">Действителен до (включ.):</label>
                                        @if ($usrrights['save'] ?? false)
                                            <input type="date" class="form-control" name="enddate"
                                                   value="{{old('enddate',$rec->enddate)}}"/>
                                        @else
                                            @if($usrrights['upd_enddate'] ?? true)
                                                <form method="put"
                                                      action="{{route('invoices.update_enddate',$rec->id)}}">
                                                    <div class="input-group">
                                                        <input type="date" class="form-control" name="enddate"
                                                               value="{{old('enddate',$rec->enddate)}}"/>
                                                        <button class="btn btn-sm btn-warning"
                                                                formaction="{{route('invoices.update_enddate',$rec->id)}}"
                                                                onclick="return confirm('Изменить дату окончания действия счета?')"
                                                                title="Сохранить изменения"><i class="fa fa-floppy-o "
                                                                                               aria-hidden="true"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                            @else
                                                <input type="date" class="form-control" name="enddate"
                                                       value="{{old('enddate',$rec->enddate)}}" readonly/>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                                @if($rec->doctypeid==2)
                                    <div class="form-group col-md-4 col-sm-6">
                                        <label for="docnum">Дата получения на объекте:</label>
                                        @if ($usrrights['save']??false)
                                            <input type="date" class="form-control" name="fctgetdate"
                                                   max="{{today()->format('Y-m-d')}}"
                                                   value="{{old('fctgetdate',$rec->fctgetdate)}}"/>
                                        @else
                                            <input type="date" class="form-control" name="fctgetdate"
                                                   readonly
                                                   value="{{$rec->fctgetdate}}"/>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="address" class="required">Поставщик:</label>
                                    <div class="input-group mb-3 ">
                                        {{ Form::hidden('orgid', old('orgid',$rec->orgid),['id'=>'orgid']) }}
                                        @if ($usrrights['save']??false)
                                            <input type="text" class="form-control" name="orgname"
                                                   id="orgname"
                                                   value="{{$rec->org->name}}"
                                                   data-toggle="tooltip" data-placement="right"
                                                   title=""
                                            />
                                            <input type="text" class="form-control text-center small"
                                                   style="display: none; border: #d7f3e3;" id="ac_orgid"
                                                   readonly>
                                            <div class="input-group-append">

                                                <a onclick="callListOrgs($('#orgid').val())" title="Поиск"
                                                   id="org_search_btn" style0="display: none"
                                                   class="btn btn-primary form-control">
                                                    <i class="fa fa-search" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                            <a href="{{route('orgs.create',0)}}" title="Добавить нового контрагента"
                                               id="new_org_link" style="display: none"
                                               target="_blank" class="btn btn-sm0 btn-warning ">
                                                <i class="fa fa-plus " aria-hidden="true"></i>
                                            </a>
                                        @else
                                            <input type="text" name="orgname" id="orgname"
                                                   class="form-control" readonly
                                                   value="{{old('orgname',$rec->org->name)}} (ИНН:{{$rec->org->inn}}, КПП:{{$rec->org->kpp}})"
                                            />
                                        @endif
                                        @if(isset($rec->orgid))
                                            <a href="{{route('orgs.edit',$rec->orgid)}}" id="org_link"
                                               target="_blank" class="btn btn-light"><i class="fa fa-external-link"
                                                                                        aria-hidden="true"></i></a>
                                        @endif

                                    </div>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="ownorgid" class="required">Покупатель:</label>
                                    <div class="input-group">
                                        @if ($usrrights['save']??false)
                                            {!! Form::select('ownorgid', $rec->ownorgs, old('ownorgid',$rec->ownorgid),
                                             [
                                             'id' => 'ownorgid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => '-выбор-',
                                             ]) !!}
                                        @else
                                            <input type="text" name="ownorgname"
                                                   class="form-control" readonly
                                                   value="{{$rec->ownorg->name}}"
                                            />
                                        @endif
                                        @if(isset($rec->ownorgid))
                                            <a href="{{route('orgs.edit',$rec->ownorgid)}}" id="ownorg_link"
                                               target="_blank" class="btn btn-light"><i class="fa fa-external-link"
                                                                                        aria-hidden="true"></i></a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($rec->doctypeid==1)
                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-12 col-sm-6">
                                        <label for="contractid">Договор(поставки):</label>
                                        <div class="input-group">
                                            @if ($usrrights['save']??false)
                                                {!! Form::select('contractid', $rec->contracts??[], old('contractid',$rec->contractid),
                                                     [
                                                     'id' => 'contractid',
                                                     'class' => 'form-control',
                                                     'placeholder' => '-выбор-',
                                                     ]) !!}
                                            @else
                                                {{ Form::hidden('contractid', $rec->contractid, ['id'=>'contractid']) }}
                                                <input type="text" name="contractid"
                                                       class="form-control" readonly
                                                       value="{{$rec->contract->info}}"
                                                />
                                            @endif
                                            @if(isset($rec->contractid))
                                                <a href="{{route('contracts.edit',$rec->contractid)}}"
                                                   id="contract_link"
                                                   target="_blank" class="btn btn-light"><i class="fa fa-external-link"
                                                                                            aria-hidden="true"></i></a>
                                            @endif
                                        </div>
                                    </div>
                                </div>


                            @endif
                            @if(1==0)
                                <div class="row">
                                    <div class="form-group offset-md-6 col-md-6">
                                        <label for="for_orgid">Для заказчика:</label>
                                        @if ($usrrights['save']??false)
                                            {!! Form::select('for_orgid', $rec->ownorgs, $rec->for_orgid,
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => '-выбор-',
                                             ]) !!}
                                        @else
                                            <input type="text" name="for_orgname"
                                                   class="form-control" readonly
                                                   value="{{$rec->for_org->name}}"
                                            />
                                        @endif
                                    </div>
                                </div>
                            @endif


                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="plnqty" class="required">Сумма {{$shortDocName}}:</label>
                                    <input type="number" class="form-control text-right font-weight-bold"
                                           name="docsum" id="docsum"
                                           min="0" step="0.01"
                                           value="{{old('docsum',$rec->docsum)}}"
                                        {{$docsum_readonly}}
                                    />
                                </div>
                                <div class="form-group col-md-8">
                                    <label for="notes" class="required">Основание, примечание:</label>
                                    @if ($usrrights['save']??false)
                                        <input type="text" class="form-control" name="notes"
                                               maxlength="160"
                                               value="{{old('notes',$rec->notes)}}"/>
                                    @else
                                        <input type="text" name="notes"
                                               class="form-control" readonly
                                               value="{{$rec->notes}}"
                                        />
                                    @endif
                                </div>
                            </div>

                            @if($rec->doctypeid==1)
                                <div class="row">
                                    <div class="form-group offset-md-4 col-md-5 col-sm-6">
                                        <label for="categoryid">Категория:</label>
                                        @if ($usrrights['save']??false and isset($rec->pay_categories[$rec->categoryid]))
                                            {!! Form::select('categoryid', $rec->pay_categories, old('categoryid',$rec->categoryid),
                                                 [
                                                 'id' => 'categoryid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                        @else
                                            {{ Form::hidden('categoryid', $rec->categoryid,['id'=>'categoryid']) }}
                                            <input type="text" name="categoryid"
                                                   class="form-control" readonly
                                                   value="{{$rec->pay_category->name}}"
                                            />
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-1 col-md-2">
                                        @if ($usrrights['save']??false)
                                            <label for="active" style="color: rgb(73, 80, 87);">Актуальный:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1,
                                             [
                                             'class' => 'form-control',
                                             ]) !!}
                                        @else
                                            <label for="active" style="color: rgb(73, 80, 87);">Статус:</label>
                                            <input type="text" class="form-control" readonly
                                                   value="{{($rec->active==1)?'рабочий':'черновик'}}"
                                            />
                                            <b></b>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-2 c7_hide0" style="background-color: #f5fac7;">
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="opertypeid">Вид работ:</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('opertypeid', $rec->opertypes, old('opertypeid',$rec->opertypeid),
                                                 [
                                                 'id' => 'opertypeid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '-выбор-',
                                                 ]) !!}
                                                @if ($errors->has('opertypeid'))
                                                    <span class="help-block text-danger">
                                                        <strong>{{ $errors->first('opertypeid') }}</strong>
                                                    </span>
                                                @endif
                                            @else
                                                <div class="font-weight-bold">{{$rec->opertypeid->name}}</div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-6 c7_hide">
                                            <label for="orgcontractid" class="required">Подрядчик / Договор:</label>
                                            @if ($usrrights['save'] and isset($rec->orgcontracts))
                                                {!! Form::select('orgcontractid', $rec->orgcontracts, old('orgcontractid',$rec->orgcontractid),
                                                 [
                                                 'id' => 'orgcontractid',
                                                 'class' => 'form-control',
                                                 'placeholder' => '',
                                                 ]) !!}
                                                @if ($errors->has('orgcontractid'))
                                                    <span class="help-block text-danger">
                                                        <strong>{{ $errors->first('orgcontractid') }}</strong>
                                                    </span>
                                                @endif

                                            @else
                                                <div class="">
                                                    <b>{{$rec->exe_org->name}}</b>
                                                    / {{$rec->exe_contract->shortInfo}}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="form-group offset-md-0 col-md-3 c7_hide">
                                            <label for="" class="">Тек. остаток средств:</label>
                                            <div id="budget_rest" class="text-right font-weight-bold">
                                                {{$rec->budget_rest}}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @else
                                {{ Form::hidden('categoryid', $rec->categoryid) }}
                                {{ Form::hidden('active', $rec->active) }}
                            @endif

                            <div class="row">
                                <div class="form-group offset-md-4 col-md-5 c8_hide c3_hide">
                                    <label for="plngetwrkdays">Срок получения в рабочих днях от даты
                                        оплаты:</label>
                                    @if ($usrrights['save']??false)
                                        <input type="number" class="form-control text-right plngetwrkdays"
                                               name="plngetwrkdays" min="0" step="1"
                                               value="{{old('plngetwrkdays',$rec->plngetwrkdays)}}"
                                        />
                                    @else
                                        <div class="">
                                            <b>{{$rec->plngetwrkdays}}</b>
                                        </div>
                                    @endif

                                </div>
                            </div>

                            <div class="row c7_show" style="{{$c7_css}}">
                                <div class="form-group col-md-4">
                                    <label for="usedsum">Использовано в заявках:</label>
                                    <input type="number" class="form-control text-right font-weight-bold"
                                           name="usedsum" id="usedsum"
                                           min="0" step="0.01"
                                           value="{{old('usedsum',$rec->usedsum)}}"
                                           readonly
                                    />
                                </div>
                            </div>

                            @if($rec->doctypeid==1)
                                <div class="row c7_show" style="{{$c7_css}}">
                                    <div class="form-group col-md-4">
                                        <label for="aux_sum">Доп. затраты:</label>
                                        @if ($usrrights['save']??false)
                                            <input type="number" class="form-control text-right font-weight-bold"
                                                   name="aux_sum" id="aux_sum"
                                                   min="0" step="0.01"
                                                   value="{{old('aux_sum',$rec->aux_sum)}}"
                                            />
                                        @else
                                            <input type="text" name="notes"
                                                   class="form-control text-right" readonly
                                                   value="{{number_format($rec->aux_sum,2)}}"
                                            />
                                        @endif
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label for="aux_descript">Вид затрат:</label>
                                        @if ($usrrights['save']??false)
                                            <input type="text" class="form-control" name="aux_descript"
                                                   maxlength="45"
                                                   value="{{old('aux_descript',$rec->aux_descript)}}"/>
                                        @else
                                            <input type="text" name="aux_descript"
                                                   class="form-control" readonly
                                                   value="{{$rec->aux_descript}}"
                                            />
                                        @endif
                                    </div>

                                </div>
                            @endif

                            @if ($rec->id == -1)
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="notes">Файл с образом документа:</label>
                                        <input type="file" class="form-control" name="docs[]" multiple
                                               accept='.pdf,.xls,.xlsx'
                                               style="padding: 3px;"/>
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
                            <a class="btn btn-close btn-info" href="{{ $retRoute }}"
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
                                @if(1==0)
                                    <a href="{{route($sysobjcode.'.delete', $rec->id)}}"
                                       onclick="return confirm('Вы действительно хотите удалить запись?')"
                                       title="Удалить запись"
                                       class="btn btn-danger btn-sm ml-4">
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </a>
                                @endif

                            @endif

                            &nbsp;
                            @if ($usrrights['send2pay'])
                                <a class="btn btn-close btn-warning ml-3 float-right"
                                   href="{{ route("invoices.send2pay",$rec->id) }}"
                                   title="Передать на оплату" id="send2pay"
                                   onclick="return confirm('Поставить счет в план платежей? Изменение счета будет заблокировано')">
                                    <i class="fa fa-share-square-o" aria-hidden="true"></i>
                                    Передать на оплату
                                </a>
                            @endif
                            @if ($usrrights['make_child_bill']??false)
                                <a class="btn btn-close btn-info ml-3 float-right"
                                   href="{{ route("invoices.create_child",$rec->id) }}"
                                   title="Перевыставить счет" id="create_child"
                                   onclick="return confirm('Создать связанный счет от текущего покупателя другому покупателю?')">
                                    <i class="fa fa-share-square-o" aria-hidden="true"></i>
                                    Перевыставить счет
                                </a>
                            @endif
                            @if ($usrrights['make_child_upd']??false)
                                <a class="btn btn-close btn-info ml-3 float-right"
                                   href="{{ route("invoices.create_child_upd",$rec->id) }}"
                                   title="Перевыставить УПД" id="create_child_upd"
                                   onclick="return confirm('Создать связанный УПД от текущего покупателя другому покупателю?')">
                                    <i class="fa fa-share-square-o" aria-hidden="true"></i>
                                    Перевыставить УПД
                                </a>
                            @endif
                        </form>
                    </div>

                    <div class="card-footer">
                        @include('layouts._who_when')
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                {{--                @include($sysobjcode.'.obj_files')--}}
                @include('objfiles.obj_files')
                {{--				@includeif($sysobjcode.'.equiprqsts')--}}
                @include($sysobjcode.'.pay_info')
                {{--                @include('obj_msgs._msgs')--}}
                @include($sysobjcode.'.linked_docs')
                @include($sysobjcode.'.child_bills')
                @include($sysobjcode.'.child_docs')
                @include($sysobjcode.'._expenses')
                @include($sysobjcode.'._contract_exes')
                @include('obj_readers._readers')
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                @includeif($sysobjcode.'._self_items')

                @includeif($sysobjcode.'.items')
            </div>
        </div>
        @if (1==1 and $rec->id != -1)
            <div class="row">
                <div class="col-md-12">
                    @includeif($sysobjcode.'.equiprqsts')
                    @includeif($sysobjcode.'.eritmsup_items')
                </div>
            </div>
        @endif
    </div>

    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

    <script src="{{ asset('js/callListOrgs.js') }}" defer></script>
    <script src="{{ asset('js/invoice_edit.js') }}" defer></script>
@endsection

@section('title')
    {{$thisTitle}}
@endsection
