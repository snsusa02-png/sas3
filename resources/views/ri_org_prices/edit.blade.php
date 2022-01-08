@extends('layouts.edit')

@section('content')
    <?php
    $sysobjid = 146;
    $thisSysObjId = 146;
    $sysobjcode = 'ri_org_prices';
    $thisTitle = "Предложение поставщика";
    $userid = \Auth::user()->id;

    if (!isset($rec)) {
        redirect()->route($sysobjcode . '.index');
        header("Location:" . route($sysobjcode . '.index'));
        die();
    }


    $retURL = ($rec->retURL)
        ? ($rec->retURL . '#' . $rec->id)
        : (($rec->orgid) ? route('orgs.edit', $rec->orgid) : route('refitems.index') . "?page=" . session($sysobjcode . '_pageno'));


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
                    <div class="card-header" style="background-color: #c8ffa0;">
                        {{$thisTitle}}
                        <a class="btn btn-close btn-light btn-sm"
                           style="float:right;"
                           href="{{ $retURL }}"
                           title="Вернуться в список">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="card-body" style="">

                        @include('layouts.err_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', $rec->id) }}"
                              enctype="multipart/form-data">
                            @method('PUT')
                            @csrf
                            {{ Form::hidden('ttt', 1) }}
                            {{ Form::hidden('returl', $retURL) }}

                            <div class="row">
                                <div class="form-group offset-md-0 col-md-12">
                                    <label for="name" class="required">Товар:</label>
                                    @if ($usrrights['save'])
                                        <div class="input-group">
                                            <input type="text" class="form-control font-weight-bold ac_name ri_name"
                                                   name="ri_name" required maxlength="60"
                                                   value="{{$rec->refitem->name}} {{$rec->refitem->itmtype->name}}"
                                                   autocomplete="off"
                                            />
                                            <input type="text" class="form-control text-center small ac_status"
                                                   style="display: none; border: #d7f3e3;" readonly>
                                            <input type="hidden" name="refitmid" id="refitmid" class="ac_id"
                                                   value="{{$rec->refitmid}}">
                                            <a class="btn btn-light id_lnk" id="refitmid_lnk" data-id="refitmid"
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

                                <div class="form-group col-md-9">
                                    <label for="suporg" class="required">Поставщик:</label>
                                    <div class="input-group mb-3 ">
                                        {{ Form::hidden('orgid', old('orgid',$rec->orgid),['id'=>'orgid']) }}
                                        @if ($usrrights['save']??false)
                                            <input type="text" class="form-control" name="orgname"
                                                   id="orgname"
                                                   value="{{old('org_name',$rec->org->name)}}"
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

                            </div>

                            <div class="row">
                                <div class="form-group offset-md-0 col-md-8">
                                    <label for="address">Местонахождение:</label>
                                    @if ($usrrights['save'])
                                        {!! Form::select('placeid', $rec->places??[], $rec->placeid,
                                         [
                                         'class' => 'form-control',
                                         'placeholder' => '-',
                                         ]) !!}
                                    @else
                                        <div
                                            class="font-weight-bold">{{$rec->places[$rec->placeid]??'-?-'}}</div>
                                    @endif
                                </div>
                            </div>


                            <div class="row">
                                <div class="form-group col-md-4 col-sm-6">
                                    <label for="price" class="required">Цена
                                        руб/<span id="unit"
                                                  class="font-weight-bold">{{$rec->refitem->unittype->name}}</span>
                                        :</label>

                                    @if ($usrrights['save']??false)
                                        <input type="number" class="form-control text-right font-weight-bold"
                                               name="price"
                                               min="0" step="0.01"
                                               value="{{old('price',$rec->price)}}"/>
                                    @else
                                        <input type="text" class="form-control" name="price" readonly
                                               value="{{$rec->price}}"/>
                                    @endif
                                </div>

                                <div class="form-group offset-md-0 col-md-4 col-sm-6">
                                    <label for="begdate" class="required">Цена действительна с:</label>
                                    @if ($usrrights['save']??false)
                                        <input type="date" class="form-control" name="begdate"
                                               value="{{old('begdate',$rec->begdate)}}"/>
                                    @else
                                        <input type="date" class="form-control" name="begdate"
                                               readonly
                                               value="{{$rec->begdate}}"/>
                                    @endif
                                </div>

                                <div class="form-group col-md-4 col-sm-6">
                                    <label for="docnum" class="required">до (включ.):</label>
                                    @if ($usrrights['save'] ?? false)
                                        <input type="date" class="form-control" name="enddate"
                                               value="{{old('enddate',$rec->enddate)}}"/>
                                    @else

                                        <input type="date" class="form-control" name="enddate"
                                               value="{{old('enddate',$rec->enddate)}}" readonly/>
                                    @endif
                                </div>
                            </div>

                            <div class="row">

                                <div class="form-group col-md-2">
                                    <label for="active" style="color: rgb(73, 80, 87);">Действует:</label>
                                    {!! Form::checkbox('active', 1, $rec->active==1, ['class'=>'form-control']) !!}
                                </div>

                                <div class="form-group offset-md-2 col-md-8">
                                    <label for="notes">Примечания:</label>
                                    @if ($usrrights['save']??false)
                                        <input type="text" class="form-control" name="notes"
                                               maxlength="160"
                                               value="{{old('notes',$rec->notes)}}"/>
                                    @else
                                        <input type="text" name="sup_notes"
                                               class="form-control" readonly
                                               value="{{$rec->notes}}"
                                        />
                                    @endif
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
                            <a class="btn btn-close btn-info" href="{{ $retURL }}"
                               title="Вернуться в список">
                                <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                Закрыть
                            </a>
                            @if ($usrrights['delete'])

                                <a href="{{route($sysobjcode.'.delete', $rec->id)."?retURL={$retURL}"}}"
                                   onclick="return confirm('Вы действительно хотите удалить запись?')"
                                   title="Удалить запись"
                                   class="btn btn-danger btn-sm ml-4">
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
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
                @include('objfiles.obj_files')
                @include('obj_readers._readers')
            </div>
        </div>

    </div>

    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

    <script src="{{ asset('js/callListOrgs.js') }}" defer></script>
    <script src="{{ asset('js/ri_org_price_edit.js') }}" defer></script>
@endsection

@section('title')
    {{$thisTitle}}
@endsection
