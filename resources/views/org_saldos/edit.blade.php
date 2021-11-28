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
        $sysobjid = 114;
        $thisSysObjId = 114;
        $sysobjcode = 'org_saldos';
        $objcode = $sysobjcode;
        $thisTitle = "Регистрация сальдо";

        $route_index = route('orgs.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;

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
                               href="{{ $rec->retURL }}"
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
                                {{ Form::hidden('retURL', $rec->retURL) }}
                                {{ Form::hidden('ttt', 1) }}

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="orgname" class="required"><span id="lbl_org">Контрагент</span>:
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
                                                       id="orgname" required
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
                                    <div class="form-group col-md-12">
                                        <label for="ownorgid" class="required">
                                            <span id="lbl_ownorg">Со стороны ГК</span>:
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
                                             'required' => 'required',
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

                                    <div class="form-group offset-md-0 col-md-5">
                                        <label for="saldo" class="required">Сальдо, руб:</label>
                                        <input type="number" class="form-control text-right font-weight-bold"
                                               name="saldo" {{$ro_mode}} required
                                               step="0.01"
                                               value="{{old('saldo',$rec->saldo)}}"/>
                                    </div>
                                    <div class="form-group col-md-4 col-sm-6">
                                        <label for="ondate" class="required">на начало дня:</label>
                                        <input type="date" class="form-control" name="ondate" {{$ro_mode}}
                                        max="{{$rec->maxdate}}" required
                                               value="{{old('ondate',$rec->ondate)}}"/>
                                    </div>

                                </div>

                                <hr>
                                @if ($usrrights['save'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $rec->retURL }}"
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
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <script src="{{ asset('js/org_saldo_edit.js') }}" defer></script>
    @endif
@endsection
@section('title')
    {{$thisTitle}}
@endsection
