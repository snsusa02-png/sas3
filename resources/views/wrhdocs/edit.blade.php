@extends('layouts.edit')

@section('content')

    @if (!isset( $rec ))
        <?php
        redirect()->route('wrhdocs.index');
        header("Location:" . route('wrhdocs.index'));
        die();
        ?>
    @else
        <?php
        $thisTitle = "Документ учета склада материалов";
        $thisSysObjCode = 'wrhdocs';
        $thisSysObjId = 204;
        $sysobjid = $thisSysObjId;

        $retURL = \Request::get('returl') ?? $rec->retURL ?? (route($thisSysObjCode . '.index') . "?page=" . session($thisSysObjCode . '_pageno') . '#' . $rec->id);

        //Отображать или нет Цену/Сумму определяется типом документа
        if ($rec->id != -1) {
            $showPrice = ($rec->doctype->useprice == 1);
            $showRelWrh = ($rec->doctype->need_relwrh == 1);
            $showPreDoc = ($rec->doctype->need_predoc == 1);
            $showRespStaff = ($rec->doctype->need_respstaffid == 1);
            $showSaleOrg = ($rec->doctypeid == 3);
            $showOrg = ($rec->doctype->need_org == 1);
            $isDocSigned = ($rec->docsigned == 1);

            $showRelWrh = ($showRelWrh or isset($rec->relwrh->id));

        } else {
            $showPrice = false;
            $showRelWrh = false;
            $showPreDoc = false;
            $showRespStaff = false;
            $showSaleOrg = false;
            $showOrg = false;
            $isDocSigned = false;
        }

        //$showPrice = isset($rec->doctype->useprice) ? false : $rec->doctype->useprice;
        $hdr_span_colno = 5;
        $tot_span_colno = 3;
        if ($showPrice) {
            $hdr_span_colno = $hdr_span_colno + 2;
            $tot_span_colno = $tot_span_colno + 0;
        }

        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        $lst_view = "wrhdocs.lst_std";
        if ($showPreDoc and $rec->doctypeid == 8) $lst_view = "wrhdocs.lst_predoc";

        ?>
        {{--		@dd($rec)--}}

        {{--dd(get_defined_vars())--}}
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
            <div class="row">
                <div class="col-md-9">
                    <div class="card mt-3">

                        @include('layouts.edit_msgs')

                        <div class="card-header">
                            <?php
                            $simplename = "";
                            if (isset($rec->doctype->forstock)) {
                                if ($rec->doctype->forstock == 1)
                                    $simplename = "ПРИХОД:";
                                elseif ($rec->doctype->forstock == -1)
                                    $simplename = "РАCХОД:";
                                else
                                    $simplename = "--";
                            }
                            ?>
                            <span class="sm-caps">{{$simplename}}</span> {{$rec->doctype->name . ' №'. $rec->docnum}}

                            <a class="btn btn-close btn-info btn-sm"
                               style="float:right;"
                               href="{{ $retURL }}"
                               title="Вернуться в список ">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>

                        </div>
                        <div class="card-body">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('wrhdocs.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                <input type="hidden" id="sale_dir">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="doctypeid" class="required">Тип документа:</label>
                                            @if ($usrrights['doctype.edit'])
                                                {!! Form::select('doctypeid', $rec->doctypes??[], old('doctypeid', $rec->doctypeid)
                                                ,['id' => 'doctypeid','class' => 'form-control', 'placeholder'=>'','required'=>'required']) !!}
                                            @else
                                                {{ Form::hidden('doctypeid', $rec->doctypeid) }}
                                                <p><b>{{$rec->doctype->name}}</b></p>
                                            @endif
                                        </div>
                                    </div>
                                    {{--                                    @if($rec->id==-1)--}}

                                    {{--                                    @else--}}
                                    {{--                                        <input type="hidden" id="doctypeid" name="doctypeid"--}}
                                    {{--                                               value="{{$rec->doctypeid}}">--}}
                                    {{--                                    @endif--}}
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="docdate" class="required">Дата:</label>
                                            @php($v=is_null($rec->docdate)?null:date("Y-m-d",strtotime($rec->docdate)))
                                            @if ($usrrights['safe_save'])
                                                <input type="date" class="form-control text-center"
                                                       name="docdate"
                                                       value="{{$v}}"
                                                       min="{{$rec->docdate_min}}"
                                                />
                                            @else
                                                {{ Form::hidden('docdate', $rec->docdate) }}
                                                <p><b>{{date_create($rec->docdate)->format('d.m.Y')}}</b></p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="docnum">№:</label>
                                            @if ($usrrights['safe_save'])
                                                <input type="text" class="form-control text-center" name="docnum"
                                                       value="{{$rec->docnum}}"
                                                       placeholder="заполнится при сохранении"
                                                />
                                            @else
                                                {{ Form::hidden('docnum', $rec->docnum) }}
                                                <p><b>{{$rec->docnum}}</b></p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="ownorg" id="ownorg_label">Владелец:</label>
                                            @if ($usrrights['ownorg.edit'])
                                                {!! Form::select('ownorgid', $rec->ownorgs, $rec->ownorgid
                                                ,['class' => 'form-control','required'=>'required']) !!}
                                            @else
                                                {{ Form::hidden('ownorgid', $rec->ownorgid) }}
                                                <p><b>{{$rec->ownorg->name}}</b></p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="form-group offset-md-0 col-md-6">
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

                                <div class="row" style="">
                                    <div class="col-md-6" id="wrh">
                                        <div class="form-group">
                                            <label for="wrhid" id="wrh_label"
                                                   class="required">{{$rec->doctype->wrh_label?:'Склад'}}
                                                :</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('wrhid', $rec->wrhs
                                                    , old('wrhid', $rec->wrhid),
                                                    ['id'=>'wrhid','class' => 'form-control','placeholder'=>'','required'=>'required']) !!}
                                            @else
                                                {{ Form::hidden('wrhid', $rec->wrhid) }}
                                                <p><b>{{$rec->wrh->name}}</b></p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6" id="box" style="display:none;">
                                        <div class="form-group">
                                            <label for="boxid"
                                                   id="box_label" class="required">{{'Отделение'}}
                                                :</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('boxid', $rec->boxes??[] ,$rec->boxid
                                                , ['id'=>'boxid','class' => 'form-control', 'placeholder'=>'','required'=>'required']) !!}
                                            @else
                                                {{ Form::hidden('boxid', $rec->boxid) }}
                                                <p><b>{{$rec->box->name}}</b></p>
                                            @endif
                                        </div>
                                    </div>

                                    <?php
                                    $t_style = "display:none;";
                                    if ($showRelWrh) $t_style = "display:block;";
                                    ?>

                                    <div class="col-md-6" id="relwrh" class="" style="{{$t_style}}">
                                        <div class="form-group">
                                            <label for="relwrhid" id="relwrh_label">
                                                {{$rec->doctype->relwrh_label?:'Связанный склад'}}:
                                            </label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('relwrhid', $rec->wrhs, $rec->relwrhid,
                                                    ['id'=>'relwrhid','class' => 'form-control', 'placeholder'=>'']) !!}
                                            @else
                                                {{ Form::hidden('relwrhid', $rec->relwrhid) }}
                                                <p><b>{{$rec->relwrh->name}}</b></p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6" id="relbox" style="{{$t_style}}">
                                        <div class="form-group">
                                            <label for="relboxid"
                                                   id="relbox_label">{{'Связанное отделение'}}
                                                :</label>
                                            @if ($usrrights['save'])
                                                {!! Form::select('relboxid', $rec->relboxes??[] ,$rec->relboxid,
                                                    ['id'=>'relboxid','class' => 'form-control', 'placeholder'=>'']) !!}
                                            @else
                                                {{ Form::hidden('relboxid', $rec->relboxid) }}
                                                <p><b>{{$rec->relbox->name}}</b></p>
                                            @endif
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    @php($t_nxtoffset = "offset-md-6")
                                    {{--									@if($showPreDoc or isset($rec->predocid))--}}
                                    <?php
                                    $t_nxtoffset = "";
                                    $t_style = "display:none;";
                                    if ($showPreDoc) $t_style = "display:block;";
                                    ?>
                                    <div class="col-md-6" id="predoc" style="{{$t_style}}">
                                        <div class="form-group">
                                            <label for="predoc">Пред. документ:</label>
                                            <p>
                                                @if(isset($rec->predocid))
                                                    <a href="{{route("wrhdocs.edit",$rec->predoc->id)}}">
                                                        {{$rec->predoc->doctype->name}}
                                                        <b>{{$rec->predoc->docnum}} {{$rec->predoc->docdate}}</b>
                                                    </a>
                                                    @if($rec->predoc->docsigned==1)
                                                        &nbsp; (Утвержден)
                                                    @endif
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    {{--									@endif--}}
                                    @if(isset($rec->childdoc))
                                        <div class="col-md-12" id="reldoc">
                                            <div class="form-group">
                                                <label for="childdoc">Связ. документ:</label>
                                                <p>
                                                    <a href="{{route("wrhdocs.edit",$rec->childdoc->id)}}">
                                                        {{$rec->childdoc->doctype->name}}
                                                        <b>№ {{$rec->childdoc->docnum??'-'}}
                                                            от {{date_create($rec->childdoc->docdate)->format('d.m.Y')}}</b>
                                                    </a>
                                                    @if($rec->childdoc->docsigned==1)
                                                        &nbsp; (Утвержден)
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                    @if (isset($rec->predocid))
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="doctypeid">Пред. документ:</label>
                                                <p>
                                                    <a href="{{route($thisSysObjCode.'.edit', $rec->predocid)}}"><b>{{$rec->predoc->info}}</b></a>
                                                    @if($rec->predoc->docsigned==1)
                                                        &nbsp; (Утвержден)
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="row">
                                    <?php
                                    $t_style = "display:none;";
                                    if ($showSaleOrg) $t_style = "display:block;";
                                    ?>

                                    <div class="col-md-7" id="saleorg" class="" style="{{$t_style}}">
                                        <label for="name" class="required"><span id="lbl_org">Продавец</span>:</label>
                                        @if ($usrrights['safe_save']??false)
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="saleorg_name" id="saleorg_name"
                                                       class="ac_name ac_org_name form-control font-weight-bold"
                                                       data-gk="{{$rec->saleorg_gk}}"
                                                       value="{{old('saleorg_name',$rec->saleorg->info)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       title=""
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="saleorgid" class="ac_id" id="saleorgid"
                                                       value="{{old('saleorgid',$rec->saleorgid)}}">
                                                <a class="btn btn-light id_lnk" data-id="saleorgid" data-obj="orgs"
                                                   target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                            <div></div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->saleorg->info}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <?php
                                    $t_style = "display:none;";
                                    if ($showOrg) $t_style = "display:block;";
                                    ?>

                                    <div class="col-md-7" id="org" class="" style="{{$t_style}}">
                                        <label for="name" class="required"><span id="lbl_org">Заказчик</span>:</label>
                                        @if ($usrrights['safe_save']??false)
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="org_name" id="org_name"
                                                       class="ac_name ac_org_name form-control font-weight-bold"
                                                       data-gk="{{$rec->org_gk}}"
                                                       value="{{old('org_name',$rec->org->info)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       title=""
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="orgid" class="ac_id" id="orgid"
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
                                </div>

                                @if (false)
                                    {{--                                @if (isset($rec->order->id))--}}
                                    <div class="form-group row">
                                        <label class="col-md-2" for="ordid">Заказ:</label>
                                        {{ Form::hidden('ordid', $rec->ordid) }}
                                        <p class="col-md-10">
                                            №<b>
                                                <a href="{{route('orders.edit',$rec->ordid)}}">{{$rec->ordid}}</a></b>
                                            клиент: <b>{{$rec->order->org->name}}</b>
                                        </p>
                                    </div>
                                @endif
                                @if ($showRespStaff)
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="respstaffid">Ответственный сотрудник:</label>
                                            @if (!$isDocSigned)
                                                {!! Form::select('respstaffid', $rec->respstafflst ,$rec->respstaffid, ['class' => 'form-control']) !!}
                                            @else
                                                {{ Form::hidden('respstaffid', $rec->respstaffid) }}
                                                <p><b>{{$rec->respstaff->staff_fio()}}</b></p>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label for="remarks">Примечания:</label>

                                    @if ($usrrights['safe_save'])
                                        <textarea class="form-control rounded-0" name="remarks" id="descript"
                                                  rows="2">{{$rec->remarks}}</textarea>
                                    @else
                                        {{ Form::hidden('remarks', $rec->remarks) }}
                                        <p><b>{{$rec->remarks}}</b></p>
                                    @endif
                                </div>


                                @if(false)
                                    <div class="form-group row mandatory">
                                        <label class="col-sm-4 form-control-label help">Язык</label>
                                        <div class="col-sm-8">
                                            <select class="form-control custom-select item-languageid"
                                                    required="required" tabindex="1" name="item[locale.languageid]">
                                                <option value="">
                                                    Сделайте выбор
                                                </option>

                                                <option value="en">
                                                    en
                                                </option>
                                                <option value="ru">
                                                    ru
                                                </option>
                                                <option value="zh" selected="selected">
                                                    zh
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-sm-12 form-text text-muted help-text" style="display: block;">
                                            Available language for the current site
                                            {{env('APP_ENV')}}</div>
                                    </div>
                                @endif

                                <hr size="1">
                                @if ($usrrights['save'] or $usrrights['safe_save'] or (!$isDocSigned and $showRespStaff))

                                    <button type="submit" class="btn btn-success">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                <?php
                                if (isset($rec->ordid)) {
                                    $route = route('orders.edit', $rec->ordid);
                                    $refTitle = "Вернуться в заказ";
                                } else {
                                    $route = route('wrhdocs.index');
                                    $refTitle = "Вернуться в список документов";
                                }
                                ?>
                                <a class="btn btn-close btn-info" href="{{ $route }}" title="{{$refTitle}}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($usrrights['delete'])
                                    <button type="submit"
                                            class="btn btn-danger"
                                            style="margin-left:24px"
                                            formaction="{{ route('wrhdocs.delete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите удалить запись?')"
                                            title="Удалить"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>

                                @elseif($usrrights['admindelete'])
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            style="margin-left:24px; margin-right:8px;"
                                            formaction="{{ route($thisSysObjCode.'.admindelete', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Документ будет удален административно - без учета ограничений!\n\nПродолжать?')"
                                            title="Административно удалить документ"
                                    >
                                        <i class="fa fa-bomb" aria-hidden="true"></i>
                                    </button>
                                @endif


                                @if ($usrrights['docsign'])
                                    <button type="submit"
                                            class="btn btn-warning hide_chngd"
                                            style="margin-left:24px"
                                            formaction="{{ route('wrhdocs.sign', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите утвердить и провести документ?')"
                                            title="Утвердить документ"
                                    >
                                        <i class="fa fa-thumbs-up" aria-hidden="true"></i>
                                        Утвердить
                                    </button>
                                @endif
                                @if ($usrrights['docunsign'])
                                    <button type="submit"
                                            class="btn btn-danger"
                                            style="margin-left:24px"
                                            formaction="{{ route('wrhdocs.unsign', $rec->id)}}"
                                            formmethod="post"
                                            onclick="return confirm('Вы действительно хотите отменить проведение документа?')"
                                            title="Отменить утверждение документа"
                                    >
                                        <i class="fa fa-thumbs-o-down" aria-hidden="true"></i>
                                    </button>
                                @endif

                                @if ($rec->id<>-1)
                                    <a class="btn btn-close btn-warning btn hide_chngd ml-3"
                                       href="{{ route($thisSysObjCode .'.print', $rec->id) }}"
                                       target="_blank" id="print_rqst"
                                       title="Напечатать">
                                        <i class="fa fa-print" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if ($usrrights['make_doc5'])
                                    <a class="btn btn-close btn-info btn hide_chngd ml-3"
                                       href="{{ route($thisSysObjCode .'.make_doc5', $rec->id) }}"
                                       {{--                                       target="_blank" id="act_raw_materials"--}}
                                       title="Создать документ на списание материалов">
                                        <i class="fa fa-cubes" aria-hidden="true"></i>
                                    </a>
                                @endif

                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">

                    @include('wrhdocs._child_docs')

                    @if ($rec->id != -1 and isset($auxinfo) and is_array($auxinfo) and count($auxinfo)>0)
                        <div class="card mt-3">
                            <div class="card-header">
                                Доп. информация
                            </div>
                            <div class="card-body">

                                <table class="table">
                                    <tbody>
                                    @foreach($auxinfo as $itm)
                                        <?php
                                        $btn_class = "btn-warning";
                                        if (isset($itm['btn-class'])) {
                                            $btn_class = $itm['btn-class'];
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                @if ($itm['route'] != "")
                                                    <a href="{{ route($itm['route'],$rec->id)}}"
                                                       class="btn btn-sm org-aux {{$btn_class}}"
                                                       title="{{$itm['name']}}">
                                                        {{$itm['name']}}
                                                    </a>
                                                @else
                                                    {{$itm['name']}}
                                                @endif
                                            </td>
                                            <td class="l small">
                                                {{$itm['sample']}}
                                            </td>
                                            <td class="r small">
                                                {{$itm['reccount']}}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

            </div>

            @if ($rec->id!=-1)
                {{--				@includeWhen($haspredoc,$lst_view)--}}
                @include($lst_view)

                @if (isset($rec->restorditems) and $rec->restorditems->count()>0)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card mt-3" style="padding:6px; min-width:660px;">
                                <table class="table-striped small"
                                       style=" width: 100%;">
                                    <thead>
                                    <tr>
                                        <td colspan="{{$hdr_span_colno}}"><h6>Неотгруженный остаток по заказу</h6></td>
                                        <td style="text-align: right;">
                                        <td>
                                    </tr>
                                    @if (count($items)>0)
                                        <?php
                                        $TotQty = 0;
                                        $TotDocSum = 0;
                                        $TotGrossWeight = 0;
                                        ?>
                                        <tr>
                                            <td>#</td>
                                            <td>Наименование</td>
                                            <td>е.и.</td>
                                            <td>Количество, е.и.</td>
                                            <td>Доступно на складе, е.и.</td>
                                            <td/>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($rec->restorditems as $itm)
                                        <?php
                                        $restQty = $itm->restqty;
                                        $stockQty = $itm->ws_qty - $itm->ws_plnoutqty;

                                        if ($stockQty < $restQty) $stockBgCol = "#ffaaaa";
                                        elseif ($stockQty == $restQty) $stockBgCol = "#ffffaa";
                                        else $stockBgCol = "#aaffaa";
                                        ?>
                                        <tr class="align-top">
                                            <td class="small">{{$loop->iteration}}</td>
                                            <td>
                                                {{$itm->refitmid}}:
                                                <b>{{$itm->ri_name}}</b>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $unitname = "";
                                                if (isset($itm->refitem->unittype->name))
                                                    $unitname = $itm->refitem->unittype->name;
                                                ?>
                                                {{$unitname}}
                                            </td>
                                            <td class="text-center " nowrap>
                                                {{number_format($restQty,3)}}
                                            </td>
                                            <td class="text-center " nowrap style="background-color: {{$stockBgCol}};">
                                                {{number_format($stockQty,3)}}
                                            </td>
                                        </tr>
                                        <?php
                                        $TotQty = $TotQty + $restQty;
                                        //                                        $TotGrossWeight = $TotGrossWeight + $restQty * $itm->ri_grossweight;
                                        ?>
                                    @endforeach
                                    <tr>
                                        <td colspan="{{$tot_span_colno}}" class="text-right">
                                            Итого:
                                        </td>
                                        <td class="text-center text-bold">
                                            <b>{{number_format($TotQty,0)}}</b>
                                        </td>
                                    </tr>
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @include('obj_finopers._finopers')

            @endif

            <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
            <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

            <script src="{{ asset('js/callListWrhs.js') }}" defer></script>
            <script src="{{ asset('js/wrhdoc_edit.js') }}" defer></script>

        </div>
    @endif
@endsection
