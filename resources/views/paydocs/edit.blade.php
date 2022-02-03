@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('contracts.index');
        header("Location:" . route('contracts.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>
        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

        <script src="{{ asset('js/callListOrgs.js') }}" defer></script>

        <?php
        $sysobjid = 520;
        $thisSysObjId = 520;
        $sysobjcode = 'paydocs';
        $objcode = $sysobjcode;
        $thisTitle = "Регистрация платежа";

        $route_index = route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;

        $ro_mode = ($usrrights['save'] ?? false) ? '' : 'readonly';
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
                <div class="col-md-7">
                    <div class="card p-2 my-2 my-md-3">
                        <div class="card-header">
							<span class="font-weight-bold"
                                  style="max-width: 60%; overflow:hidden;"> {{$thisTitle}}</span>

                            <span class="float-right">
							<a class="btn btn-close btn-light btn-sm ml-1"
                               href="{{ $route_index }}"
                               title="Вернуться в список">
								<i class="fa fa-times" aria-hidden="true"></i>
							</a>
							</span>
                        </div>
                        <div class="card-body" style="background-color: #f4f4f4">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($sysobjcode . '.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('id', $rec->id,['id'=>'id']) }}
                                {{ Form::hidden('ttt', 1) }}


                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="ownorgid" class="required">
                                            <span id="lbl_ownorg">Со стороны ГК </span><span id="ownorg_aux_lbl"></span>:
                                            @if(isset($rec->ownorgid))
                                                <a href="{{route("orgs.edit",$rec->ownorgid)}}"
                                                   target="_blank"><i class="fa fa-external-link-square text-info"
                                                                      aria-hidden="true"></i></a>
                                            @endif
                                        </label>
                                        @if($usrrights['save']??false)
                                            {!! Form::select('ownorgid', $rec->ownorgs, $rec->ownorgid,
                                             [
                                             'id' => 'ownorgid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             ]) !!}
                                        @else
                                            <input type="text" name="ownorgname"
                                                   class="form-control" readonly
                                                   value="{{$rec->ownorg->name}}"
                                            />
                                        @endif
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label for="categoryid" class="required">Тип:</label>
                                        @if($usrrights['save']??false)
                                            {!! Form::select('paydir', $rec->paydirs, $rec->paydir,
                                             [
                                             'id' => 'paydir',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        @else
                                            <input type="text" name="paydir"
                                                   class="form-control" readonly
                                                   value="{{$rec->paydirs[$rec->paydir]??''}}"
                                            />
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-5">
                                        <label for="paysum" class="required">Сумма, руб:</label>
                                        <input type="number" class="form-control text-right font-weight-bold"
                                               name="paysum" {{$ro_mode}} required
                                               min="0" step="0.01"
                                               value="{{old('paysum',$rec->paysum)}}"/>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="categoryid" class="required">Форма оплаты:</label>
                                        @if($usrrights['save']??false)
                                            {!! Form::select('paytypeid', $rec->paytypes, $rec->paytypeid,
                                             [
                                             'id' => 'paytypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        @else
                                            <input type="text"
                                                   class="form-control" readonly
                                                   value="{{$rec->paytypes[$rec->paytypeid]??''}}"
                                            />
                                        @endif
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="form-group col-md-12">
                                        <label for="orgname" class="required"><span id="lbl_org">Контрагент</span> <span
                                                id="org_aux_lbl"></span>:
                                            @if(isset($rec->orgid))
                                                <a href="{{route("orgs.edit",$rec->orgid)}}"
                                                   target="_blank"><i class="fa fa-external-link-square text-info"
                                                                      aria-hidden="true"></i></a>
                                            @endif
                                            @if(1==1)
                                                <a href="{{route("orgs.create",0)}}"
                                                   target="_blank"><i class="fa fa-plus-square-o badge-warning"
                                                                      aria-hidden="true"></i></a>
                                            @endif
                                        </label>
                                        <div class="input-group">
                                            {{ Form::hidden('orgid', old('orgid',$rec->orgid),['id'=>'orgid']) }}
                                            @if ($usrrights['save']??false)
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
                                            @else
                                                <input type="text" name="orgname"
                                                       class="form-control" readonly
                                                       value="{{$rec->org->name}} (ИНН:{{$rec->org->inn}}, КПП:{{$rec->org->kpp}})"
                                                />
                                            @endif
                                        </div>

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

                                    <div class="form-group offset-md-0 col-md-6">
                                        <label for="name" class="">Вид работ:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('opertypeid', $rec->opertypes??[], old('opertypeid',$rec->opertypeid),
                                             [
                                                 'id' => 'opertypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        @else
                                            <input type="text" class="form-control" readonly
                                                   value="{{$rec->opertype->name}}"
                                            />
                                        @endif
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="form-group offset-md-0 col-md-3 col-sm-6 ">
                                        <label for="docnum" class="">№ док-та:</label>
                                        <input type="text" class="form-control text-center font-weight-bold"
                                               name="docnum" {{$ro_mode}}
                                               value="{{old('docnum',$rec->docnum)}}"/>
                                    </div>
                                    <div class="form-group col-md-4 col-sm-6">
                                        <label for="docnum" class="">Дата док-та:</label>
                                        <input type="date" class="form-control" name="docdate" id="docdate" {{$ro_mode}}
                                        value="{{old('docdate',$rec->docdate)}}"/>
                                    </div>

                                    <div class="form-group col-md-5 col-sm-6">
                                        <label for="paydate" class="required" id="lbl_paydate">Дата
                                            прихода/расхода:</label>
                                        <input type="date" class="form-control" name="paydate" id="paydate" {{$ro_mode}}
                                        min="{{$rec->paydate_min}}" max="{{$rec->maxdate}}" required
                                               value="{{old('paydate',$rec->paydate)}}"/>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12 col-sm-6 ">
                                        <label for="docnum">Основание:</label>
                                        <input type="text" class="form-control text-left" name="reason"
                                               value="{{old('reason',$rec->reason)}}" {{$ro_mode}}
                                               maxlength="160"/>
                                    </div>
                                </div>

                                <div class="row">
                                </div>

                                <hr>
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения">
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
                                            formaction="{{ route($sysobjcode . '.delete', $rec->id)}}"
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
                                &nbsp;
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>

                @if($rec->id<>-1)
                    <div class="col-md-3">
                        @include('objfiles.obj_files')
                        {{--                        @include('obj_readers._readers')--}}
                    </div>
                @endif
            </div>
        </div>
        <script src="{{ asset('js/paydoc_edit.js') }}" defer></script>
    @endif
@endsection
@section('title')
    {{$thisTitle}}
@endsection
